# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

Mobile-first PWA, installed on phones and played in portrait. Usable on tablet and desktop, but the game itself is designed for one phone held upright.

## Users

Groups of friends, roughly 16–25, playing together on **one shared phone**. The phone lies on the table or gets passed around; whoever holds it reads the card out loud.

- **Players** (no account): enter names, pick a deck, play.
- **Deck makers** (paid, one-time): write their own decks and share them via a link.
- **Administrators**: curate the base-game decks, moderate reported decks, manage users.

## Product Purpose

A drinking card game that runs the evening for the group: it picks who has to do what, so nobody has to invent dares or keep track. Success is a group that keeps swiping for the whole round and plays again next time.

## Positioning

Cards are dynamic, not a fixed pack: every card names real players from the group and re-rolls amounts on each play, so the same deck reads differently every game. Anyone with the deck maker can build and share their own deck with visual variable blocks, and decks are an open, documented JSON format.

## Operating Context

Played at **pregames / house parties**, in **bars (de kroeg)**, and at **student houses and verenigingen** where the same group plays regularly.

- Noisy, often dim rooms; people half-paying attention, phone passed between hands.
- One tap flips the first card, every card after that is a swipe; the whole round must work without anyone reading instructions.
- Sessions are in-memory: closing the app ends the game.

## Capabilities and Constraints

- Players enter names (minimum 2), pick a base-game or own deck, play until the deck's ending: after N cards, or when every card has been played once.
- Card types: drinking, challenge (opdracht), truth, dare, vote, custom. Variables: player 1–10, all players, amount (fixed or random range).
- One-card undo; sound and haptics with settings to switch them off; reduced-motion support.
- Dutch is primary, English secondary.
- Deck maker is a one-time purchase via Lemon Squeezy (pay-what-you-want, minimum €9.99). "Doneer een biertje" donations are open to guests and unlock nothing.
- Stack is fixed: Laravel 13 + Inertia + Vue 3 + TypeScript + Tailwind 4, deployed to DirectAdmin shared hosting. Adding dependencies needs approval.

## Brand Commitments

- Name: **Biertappen**.
- **Voice: cheeky Dutch party slang.** Loud, teasing, friendly-rude, talks like the group does ("Adtje!", "Proost, sukkel"). Dutch copy leads; English is a translation, not the source.
- Requested direction: a distinct **game** aesthetic for teenagers and young adults; the current look is generic and must not be kept.

## Evidence on Hand

- One seeded base deck: `database/seeders/decks/klassiek.json` (20 cards, Dutch).
- App icons in `public/icons/` are generated placeholders, not a real logo.
- No logo, mascot, illustrations, testimonials, user counts or press exist. Do not fabricate any.

## Product Principles

1. **The phone is the game master.** It decides, the group plays; nobody should need to explain the app.
2. **Any drink counts.** It is a *drankspel*, not an alcohol requirement: copy says *slokken*, never demands beer. No age gate.
3. **Tap once, then swipe.** Gameplay screens must be operable half-drunk, one-handed, in a dark room.
4. **Your group, not a generic pack.** Real names on every card is the core magic; surface it.

## Accessibility & Inclusion

- Readable in dim, noisy rooms: large type, strong contrast, never colour-only meaning.
- Fully playable with sound, haptics and motion off; honours `prefers-reduced-motion`.
- Portrait-first with a landscape guard; large touch targets.
