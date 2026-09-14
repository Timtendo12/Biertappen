import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import CardStack from './CardStack.vue';
import nl from '@/i18n/nl';
import type { ResolvedCard } from '@/game';

/*
 * Interaction tests for the one component the whole game is played through.
 * Pointer events are synthesised rather than driven by a real browser, which is
 * enough to prove the thresholds and the keyboard path — the visual polish is
 * verified by hand on a device.
 */

const i18n = createI18n({ legacy: false, locale: 'nl', messages: { nl } });

const card: ResolvedCard = {
    playId: 'play-1',
    cardId: '1',
    type: 'drinking',
    segments: [
        { type: 'player', variable: 'player1', player: { id: 'p1', name: 'Tim' }, display: 'Tim' },
        { type: 'text', value: ' neemt ' },
        { type: 'amount', variable: 'amount', amount: 3, display: '3' },
        { type: 'text', value: ' slokken.' },
    ],
    players: [{ id: 'p1', name: 'Tim' }],
    text: 'Tim neemt 3 slokken.',
};

function mountStack(revealed = false) {
    return mount(CardStack, {
        props: { card, revealed, remaining: 3 },
        global: { plugins: [i18n] },
    });
}

/** happy-dom has no pointer capture; the component calls it during a drag. */
function stubPointerCapture(element: Element): void {
    Object.assign(element, {
        setPointerCapture: vi.fn(),
        releasePointerCapture: vi.fn(),
    });
}

function pointer(type: string, clientX: number): Event {
    const event = new Event(type, { bubbles: true });
    Object.assign(event, { pointerId: 1, clientX, clientY: 0 });

    return event;
}

beforeEach(() => {
    setActivePinia(createPinia());
    vi.useFakeTimers();
    // jsdom/happy-dom report width 0 otherwise, making every swipe pass the threshold.
    Object.defineProperty(window, 'innerWidth', { value: 400, configurable: true });
});

describe('revealing', () => {
    it('emits reveal when the face-down card is tapped', async () => {
        const wrapper = mountStack(false);

        await wrapper.find('[role="button"]').trigger('click');

        expect(wrapper.emitted('reveal')).toHaveLength(1);
    });

    it('does not re-emit reveal once the card is face up', async () => {
        const wrapper = mountStack(true);

        await wrapper.find('[role="button"]').trigger('click');

        expect(wrapper.emitted('reveal')).toBeUndefined();
    });

    it('reveals on Enter and Space for keyboard players', async () => {
        const wrapper = mountStack(false);
        const target = wrapper.find('[role="button"]');

        await target.trigger('keydown', { key: 'Enter' });
        await target.trigger('keydown', { key: ' ' });

        expect(wrapper.emitted('reveal')).toHaveLength(2);
    });

    it('describes the current interaction in its accessible name', async () => {
        expect(mountStack(false).find('[role="button"]').attributes('aria-label'))
            .toBe(nl.game.tapToReveal);

        expect(mountStack(true).find('[role="button"]').attributes('aria-label'))
            .toBe(nl.game.swipeToDiscard);
    });
});

describe('swiping', () => {
    it('discards when dragged past the threshold', async () => {
        const wrapper = mountStack(true);
        const target = wrapper.find('[role="button"]');
        stubPointerCapture(target.element);

        target.element.dispatchEvent(pointer('pointerdown', 200));
        target.element.dispatchEvent(pointer('pointermove', 40));
        target.element.dispatchEvent(pointer('pointerup', 40));
        await wrapper.vm.$nextTick();

        // The discard is emitted after the fly-out animation settles.
        expect(wrapper.emitted('discard')).toBeUndefined();
        vi.runAllTimers();

        expect(wrapper.emitted('discard')?.[0]).toEqual(['left']);
    });

    it('discards to the right as well — either direction works', async () => {
        const wrapper = mountStack(true);
        const target = wrapper.find('[role="button"]');
        stubPointerCapture(target.element);

        target.element.dispatchEvent(pointer('pointerdown', 100));
        target.element.dispatchEvent(pointer('pointermove', 300));
        target.element.dispatchEvent(pointer('pointerup', 300));
        vi.runAllTimers();

        expect(wrapper.emitted('discard')?.[0]).toEqual(['right']);
    });

    it('springs back instead of discarding on a short drag', async () => {
        const wrapper = mountStack(true);
        const target = wrapper.find('[role="button"]');
        stubPointerCapture(target.element);

        target.element.dispatchEvent(pointer('pointerdown', 200));
        target.element.dispatchEvent(pointer('pointermove', 215));
        target.element.dispatchEvent(pointer('pointerup', 215));
        vi.runAllTimers();

        expect(wrapper.emitted('discard')).toBeUndefined();
    });

    it('ignores drags while the card is still face down', async () => {
        const wrapper = mountStack(false);
        const target = wrapper.find('[role="button"]');
        stubPointerCapture(target.element);

        target.element.dispatchEvent(pointer('pointerdown', 300));
        target.element.dispatchEvent(pointer('pointermove', 10));
        target.element.dispatchEvent(pointer('pointerup', 10));
        vi.runAllTimers();

        expect(wrapper.emitted('discard')).toBeUndefined();
    });

    it('discards with the arrow keys', async () => {
        const wrapper = mountStack(true);

        await wrapper.find('[role="button"]').trigger('keydown', { key: 'ArrowLeft' });
        vi.runAllTimers();

        expect(wrapper.emitted('discard')?.[0]).toEqual(['left']);
    });
});

describe('rendering', () => {
    it('exposes the whole card as one sentence for screen readers', () => {
        expect(mountStack(true).find('.sr-only').text()).toBe('Tim neemt 3 slokken.');
    });

    it('renders resolved values, never variable placeholders', () => {
        const html = mountStack(true).html();

        expect(html).toContain('Tim');
        expect(html).not.toContain('player1');
        expect(html).not.toContain('{');
    });

    it('labels the card type in words, not only by colour', () => {
        expect(mountStack(true).text()).toContain(nl.cardTypes.drinking);
    });
});
