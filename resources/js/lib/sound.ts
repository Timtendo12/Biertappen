/**
 * Interaction sounds, synthesised rather than loaded.
 *
 * Two short, generated tones weigh nothing, need no network request, work
 * offline from the first launch, and avoid shipping audio files the service
 * worker would have to cache. Every entry point is guarded: a browser without
 * Web Audio simply plays nothing, it never throws.
 */

type SoundName = 'flip' | 'swipe' | 'finish';

let context: AudioContext | null = null;
let unlocked = false;

function audioContext(): AudioContext | null {
    if (context) {
        return context;
    }

    const Ctor =
        typeof window !== 'undefined'
            ? (window.AudioContext ?? (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext)
            : undefined;

    if (!Ctor) {
        return null;
    }

    try {
        context = new Ctor();
        return context;
    } catch {
        return null;
    }
}

export function soundSupported(): boolean {
    return audioContext() !== null;
}

/**
 * Mobile browsers only allow audio to start inside a user gesture. Called from
 * the first tap of a game so every later sound is already permitted.
 */
export function unlockAudio(): void {
    if (unlocked) return;

    const ctx = audioContext();
    if (!ctx) return;

    void ctx.resume().catch(() => undefined);
    unlocked = true;
}

interface ToneSpec {
    from: number;
    to: number;
    duration: number;
    type: OscillatorType;
    gain: number;
}

const TONES: Record<SoundName, ToneSpec[]> = {
    // A short downward click: the sound of a card landing face up.
    flip: [{ from: 880, to: 320, duration: 0.12, type: 'triangle', gain: 0.09 }],
    // A brief upward sweep that reads as movement away from the stack.
    swipe: [{ from: 260, to: 720, duration: 0.16, type: 'sine', gain: 0.07 }],
    // Two notes, so the end of a game is unmistakable without being a jingle.
    finish: [
        { from: 520, to: 520, duration: 0.14, type: 'triangle', gain: 0.08 },
        { from: 780, to: 780, duration: 0.22, type: 'triangle', gain: 0.08 },
    ],
};

export function playSound(name: SoundName): void {
    const ctx = audioContext();
    if (!ctx) return;

    try {
        let offset = 0;

        for (const tone of TONES[name]) {
            const start = ctx.currentTime + offset;
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = tone.type;
            oscillator.frequency.setValueAtTime(tone.from, start);

            if (tone.to !== tone.from) {
                oscillator.frequency.exponentialRampToValueAtTime(tone.to, start + tone.duration);
            }

            // Ramp down to silence: an abrupt stop produces an audible click.
            gain.gain.setValueAtTime(tone.gain, start);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + tone.duration);

            oscillator.connect(gain).connect(ctx.destination);
            oscillator.start(start);
            oscillator.stop(start + tone.duration);

            offset += tone.duration * 0.75;
        }
    } catch {
        // Audio is a nicety; never let it interrupt a game.
    }
}
