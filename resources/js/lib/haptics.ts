/**
 * Haptic feedback.
 *
 * Vibration is unsupported on iOS Safari and blocked in some Android contexts,
 * so every call is feature-detected and failure is silent. The game must be
 * fully playable with haptics unavailable or switched off.
 */

type HapticPattern = 'tap' | 'flip' | 'discard' | 'finish';

const PATTERNS: Record<HapticPattern, number | number[]> = {
    tap: 10,
    flip: 18,
    discard: [12, 24, 12],
    finish: [30, 60, 30],
};

export function hapticsSupported(): boolean {
    return typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function';
}

export function vibrate(pattern: HapticPattern): void {
    if (!hapticsSupported()) return;

    try {
        navigator.vibrate(PATTERNS[pattern]);
    } catch {
        // Some browsers throw when vibration is disabled at the OS level.
    }
}
