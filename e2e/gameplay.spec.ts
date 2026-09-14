import { expect, test, type Page } from '@playwright/test';

/**
 * The definition of done from the product spec, walked end to end in a real
 * browser on a portrait phone.
 *
 * The engine's rules are covered exhaustively by unit tests. What only a browser
 * can prove is the part those cannot touch: that a tap really flips the card,
 * that a drag past the threshold really discards it, and that the next card
 * really reveals itself without a second tap.
 */

const PLAYERS = ['Tim', 'Lisa', 'Mark', 'Emma'];

async function enterPlayers(page: Page, names = PLAYERS): Promise<void> {
    await page.goto('/');

    for (const [index, name] of names.entries()) {
        if (index >= 2) {
            await page.getByRole('button', { name: /speler toevoegen/i }).click();
        }

        await page.getByRole('textbox').nth(index).fill(name);
    }
}

/** The card element, which is the only thing on screen with this role+label. */
function card(page: Page) {
    return page.getByRole('button', { name: /tik om om te draaien|swipe om weg te leggen/i });
}

/**
 * Drags the card far enough to pass the discard threshold.
 *
 * Real pointer events rather than a synthetic one: the component tracks
 * pointerdown/move/up and uses pointer capture, so anything less would test a
 * different code path than a finger does.
 */
async function swipeAway(page: Page, direction: 'left' | 'right' = 'left'): Promise<void> {
    const target = card(page);
    const box = await target.boundingBox();

    if (!box) throw new Error('Card is not visible');

    const startX = box.x + box.width / 2;
    const y = box.y + box.height / 2;
    const endX = direction === 'left' ? startX - box.width * 1.5 : startX + box.width * 1.5;

    await page.mouse.move(startX, y);
    await page.mouse.down();
    // Several intermediate moves: one jump can be swallowed as a click.
    for (let step = 1; step <= 6; step++) {
        await page.mouse.move(startX + ((endX - startX) * step) / 6, y);
    }
    await page.mouse.up();

    // Let the fly-out animation settle and the next card mount.
    await page.waitForTimeout(500);
}

/**
 * Records the card's Y-rotation every animation frame for a moment.
 *
 * Returns the first column of the transform matrix: 1 means face down (0°),
 * -1 face up (180°), anything between is mid-flip. Asserting on text alone
 * cannot tell "turned over" apart from "appeared already turned over" — which
 * is exactly the bug this exists to catch.
 */
async function recordFlip(page: Page, durationMs = 1000): Promise<number[]> {
    return page.evaluate(
        (duration) =>
            new Promise<number[]>((resolve) => {
                const samples: number[] = [];
                const started = performance.now();

                const read = (): void => {
                    const inner = document.querySelector<HTMLElement>('[role="button"][tabindex="0"] > div');
                    const transform = inner ? getComputedStyle(inner).transform : 'none';
                    const match = transform.match(/matrix(?:3d)?\(([^,]+)/);

                    samples.push(match ? Number(match[1]) : 1);

                    if (performance.now() - started < duration) {
                        requestAnimationFrame(read);
                    } else {
                        resolve(samples);
                    }
                };

                requestAnimationFrame(read);
            }),
        durationMs,
    );
}

/** True when the samples show a real turn: face down, then in between, then face up. */
function turnedOver(samples: number[]): boolean {
    const faceDown = samples.findIndex((v) => v > 0.95);
    const midway = samples.findIndex((v, i) => i > faceDown && v < 0.9 && v > -0.9);

    return faceDown !== -1 && midway !== -1 && (samples.at(-1) ?? 0) < -0.95;
}

async function startGame(page: Page): Promise<void> {
    await enterPlayers(page);
    await page.getByRole('button', { name: /kies een deck/i }).click();

    await expect(page.getByRole('heading', { name: /kies een deck/i })).toBeVisible();
    await page.getByRole('button', { name: /klassiek/i }).first().click();

    await expect(card(page)).toBeVisible();
}

test.describe('player entry', () => {
    test('will not start a game with fewer than two players', async ({ page }) => {
        await page.goto('/');

        const start = page.getByRole('button', { name: /kies een deck/i });

        await expect(start).toBeDisabled();
        // The reason is stated rather than left to be inferred from a grey button.
        await expect(page.getByText(/minimaal 2 spelers/i)).toBeVisible();

        await page.getByRole('textbox').nth(0).fill('Tim');
        await page.getByRole('textbox').nth(1).fill('Lisa');

        await expect(start).toBeEnabled();
    });

    test('accepts four players and reaches deck selection', async ({ page }) => {
        await enterPlayers(page);
        await page.getByRole('button', { name: /kies een deck/i }).click();

        await expect(page.getByRole('heading', { name: /kies een deck/i })).toBeVisible();
        await expect(page.getByText(/klassiek biertappen/i)).toBeVisible();
    });
});

test.describe('the gameplay loop', () => {
    test('tapping the card flips it and reveals a resolved instruction', async ({ page }) => {
        await startGame(page);

        // Face down: the prompt is showing, no card text yet.
        await expect(page.getByText(/tik om om te draaien/i).first()).toBeVisible();

        await card(page).click();
        await page.waitForTimeout(700);

        const text = await page.locator('.sr-only').first().textContent();

        expect(text?.trim().length ?? 0).toBeGreaterThan(0);
        // Resolved values only — a leaked placeholder would mean the engine did
        // not run before render.
        expect(text).not.toContain('player1');
        expect(text).not.toContain('{');
    });

    test('resolves player variables to names that were actually entered', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        // Play a few cards; across them at least one must name a real player.
        let sawAPlayer = false;

        for (let i = 0; i < 6; i++) {
            const text = (await page.locator('.sr-only').first().textContent()) ?? '';

            if (PLAYERS.some((name) => text.includes(name))) {
                sawAPlayer = true;
                break;
            }

            await swipeAway(page);
        }

        expect(sawAPlayer).toBe(true);
    });

    test('swiping discards the card and the next one reveals itself', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        const first = await page.locator('.sr-only').first().textContent();

        await swipeAway(page, 'left');

        // The next card is already face up: one tap per game, not per card.
        await expect(page.getByText(/swipe om weg te leggen/i).first()).toBeVisible();

        const second = await page.locator('.sr-only').first().textContent();

        expect(second).not.toBe(first);
        await expect(page.getByText(/kaart 2 van/i)).toBeVisible();
    });

    test('the first card visibly turns over when tapped', async ({ page }) => {
        await startGame(page);

        const recording = recordFlip(page);
        await card(page).click();

        expect(turnedOver(await recording)).toBe(true);
    });

    test('the next card visibly turns over after a swipe, not just appears face up', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        const box = await card(page).boundingBox();
        if (!box) throw new Error('Card is not visible');

        const x = box.x + box.width / 2;
        const y = box.y + box.height / 2;

        await page.mouse.move(x, y);
        await page.mouse.down();
        for (let step = 1; step <= 6; step++) {
            await page.mouse.move(x - (box.width * 1.5 * step) / 6, y);
        }

        // Start recording before release so the new card's first frames are caught.
        const recording = recordFlip(page, 1400);
        await page.mouse.up();

        expect(turnedOver(await recording)).toBe(true);
    });

    test('an undone card turns over again rather than popping back face up', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        await swipeAway(page);
        await page.waitForTimeout(700);

        const recording = recordFlip(page);
        await page.getByRole('button', { name: /ongedaan maken/i }).click();

        expect(turnedOver(await recording)).toBe(true);
    });

    test('swipes in either direction', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        await swipeAway(page, 'right');
        await expect(page.getByText(/kaart 2 van/i)).toBeVisible();

        await swipeAway(page, 'left');
        await expect(page.getByText(/kaart 3 van/i)).toBeVisible();
    });

    test('a short drag springs back instead of discarding', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        const target = card(page);
        const box = await target.boundingBox();

        if (!box) throw new Error('Card is not visible');

        const startX = box.x + box.width / 2;
        const y = box.y + box.height / 2;

        await page.mouse.move(startX, y);
        await page.mouse.down();
        await page.mouse.move(startX - 20, y);
        await page.mouse.up();
        await page.waitForTimeout(400);

        // Still on the first card: a nudge must not throw the card away.
        await expect(page.getByText(/kaart 1 van/i)).toBeVisible();
    });
});

test.describe('undo', () => {
    test('is unavailable until something has been discarded', async ({ page }) => {
        await startGame(page);
        await card(page).click();

        await expect(page.getByRole('button', { name: /ongedaan maken/i })).toBeDisabled();
    });

    test('brings back exactly the discarded card', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        const original = await page.locator('.sr-only').first().textContent();

        await swipeAway(page);
        await expect(page.getByText(/kaart 2 van/i)).toBeVisible();

        await page.getByRole('button', { name: /ongedaan maken/i }).click();
        await page.waitForTimeout(400);

        // The same card, with the same resolved names and numbers.
        expect(await page.locator('.sr-only').first().textContent()).toBe(original);
        await expect(page.getByText(/kaart 1 van/i)).toBeVisible();
    });

    test('steps back only one card', async ({ page }) => {
        await startGame(page);
        await card(page).click();
        await page.waitForTimeout(700);

        await swipeAway(page);
        await swipeAway(page);

        const undo = page.getByRole('button', { name: /ongedaan maken/i });

        await undo.click();
        await page.waitForTimeout(300);

        await expect(undo).toBeDisabled();
    });
});

test.describe('settings', () => {
    test('switches the interface language immediately', async ({ page }) => {
        await page.goto('/settings');

        await expect(page.getByRole('heading', { name: 'Instellingen' })).toBeVisible();

        await page.getByRole('radio', { name: 'English' }).click();

        await expect(page.getByRole('heading', { name: 'Settings' })).toBeVisible();

        // And it survives a reload, because the server was told about it.
        await page.reload();
        await expect(page.getByRole('heading', { name: 'Settings' })).toBeVisible();

        await page.getByRole('radio', { name: 'Nederlands' }).click();
        await expect(page.getByRole('heading', { name: 'Instellingen' })).toBeVisible();
    });

    test('remembers sound and haptics across a reload', async ({ page }) => {
        await page.goto('/settings');

        const sound = page.getByRole('switch', { name: /geluid/i });

        await expect(sound).toBeChecked();
        await sound.uncheck({ force: true });

        await page.reload();

        await expect(page.getByRole('switch', { name: /geluid/i })).not.toBeChecked();
    });
});

test.describe('the premium gate', () => {
    test('keeps the deck creator away from anonymous visitors', async ({ page }) => {
        const response = await page.goto('/creator');

        // Redirected to login rather than shown the creator.
        expect(page.url()).toContain('/login');
        expect(response?.status()).toBeLessThan(400);
    });
});
