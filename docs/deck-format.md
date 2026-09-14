# Biertappen deck format (v1)

The interchange format for importing and exporting Biertappen decks.

This is a **public data contract**, not an internal dump. It carries no database
identifiers, and the application validates every import against the JSON Schema
at [`schemas/biertappen-deck-v1.schema.json`](../schemas/biertappen-deck-v1.schema.json) —
the same file that documents it. A deck that validates against that schema and
the rules below will import.

The format is designed so an AI can be handed this document alone and produce a
valid deck.

---

## 1. Shape

```json
{
  "format": "biertappen-deck",
  "version": 1,
  "deck": {
    "name": "Klassiek Biertappen",
    "description": "De klassiekers. Simpel, snel en altijd raak.",
    "locale": "nl",
    "tags": ["klassiek"],
    "ending": { "mode": "cards", "count": 30 },
    "cards": [
      {
        "type": "drinking",
        "participants": 1,
        "content": [
          { "type": "variable", "value": "player1" },
          { "type": "text", "value": " neemt " },
          { "type": "variable", "value": "amount", "options": { "mode": "range", "min": 1, "max": 4 } },
          { "type": "text", "value": " slokken." }
        ]
      }
    ]
  }
}
```

`format` and `version` must be exactly `"biertappen-deck"` and `1`.

**Unknown properties are rejected, not ignored.** A typo in a key is an error
rather than a silently dropped field.

---

## 2. Deck

| Field | Required | Notes |
|---|---|---|
| `name` | yes | 1–120 characters |
| `description` | yes | up to 1000 characters; may be `""` |
| `locale` | no | language the *cards* are written in, e.g. `"nl"`, `"en"`. Independent of the interface language. Defaults to `"nl"` |
| `tags` | no | up to 20 lowercase tags |
| `ending` | yes | see below |
| `cards` | yes | 1–2000 cards |

### Ending

Two modes, and the shape differs between them:

Stop after a fixed number of cards:

```json
{ "mode": "cards", "count": 30 }
```

Stop once every playable card has been seen exactly once:

```json
{ "mode": "all_cards_once" }
```

`count` is **required** for `cards` and **must be absent** for `all_cards_once`.
Including it in the second form is an error, not a harmless extra.

In `all_cards_once`, no card repeats before the deck is exhausted. Note the
length depends on the group: cards needing more players than are present are
excluded from the game entirely, so a deck of 20 cards may be a 14-card game.

---

## 3. Cards

| Field | Required | Notes |
|---|---|---|
| `id` | no | your own identifier, to keep re-imports stable. Never a database id |
| `type` | yes | lowercase slug, `^[a-z0-9_-]+$` |
| `participants` | yes | a number 1–10, or the string `"all"` |
| `content` | yes | 1–100 ordered segments |

### `type`

Free-form. The app styles and labels these:

`drinking` · `challenge` · `truth` · `dare` · `vote` · `custom`

Any other slug is **valid** and renders with the generic card style. Inventing a
type is never a breaking change and needs no schema update.

### `participants`

How many **distinct** players the card needs. The engine draws that many
different people and binds them to `player1`, `player2`, … in order.

`"all"` targets the whole group.

This is the field that decides which variables are legal — see below.

---

## 4. Content segments

Text and variables are stored **separately**. There is no template syntax and no
placeholder to parse: the app never scans a string for `{player1}`.

A text segment:

```json
{ "type": "text", "value": " neemt " }
```

A variable segment:

```json
{ "type": "variable", "value": "player1" }
```

Text segments carry their own spacing. `"Tim"` + `"neemt"` with no space between
the segments renders as `Tim neemt` only if a text segment supplies the space —
so write `" neemt "`, with leading and trailing spaces, as above.

### Available variables

| Variable | Resolves to |
|---|---|
| `player1` … `player10` | a distinct player, drawn at random |
| `all_players` | every player, joined naturally (`"Tim, Lisa en Mark"`) |
| `amount` | a number of sips |

### The three rules that are not in the schema

JSON Schema cannot express these, so the app checks them separately. They are
the most common reasons a hand-written deck is rejected:

1. **A card may not reference a player it did not declare.** With
   `"participants": 2` you may use `player1` and `player2`, and nothing higher.
2. **`all_players` requires `"participants": "all"`**, and conversely an
   `"all"` card may not use `player1`…`player10`.
3. **An `amount` range must not be inverted** — `min` must be ≤ `max`.

### `amount` options

Optional. Omitted means a random 1–5.

Always exactly three:

```json
{ "type": "variable", "value": "amount", "options": { "mode": "fixed", "value": 3 } }
```

A new number between 2 and 6 each time the card is played:

```json
{ "type": "variable", "value": "amount", "options": { "mode": "range", "min": 2, "max": 6 } }
```

A **range re-rolls every time the card is played**, so a card drawn twice in one
game reads differently. A fixed value never changes.

`options` belongs only on `amount`. Putting it on a player variable is an error.

---

## 5. Worked examples

**One player, a random amount**

```json
{
  "type": "drinking",
  "participants": 1,
  "content": [
    { "type": "variable", "value": "player1" },
    { "type": "text", "value": " neemt " },
    { "type": "variable", "value": "amount", "options": { "mode": "range", "min": 1, "max": 4 } },
    { "type": "text", "value": " slokken." }
  ]
}
```
→ *"Lisa neemt 3 slokken."*

**Two players, guaranteed different**

```json
{
  "type": "challenge",
  "participants": 2,
  "content": [
    { "type": "variable", "value": "player1" },
    { "type": "text", "value": " kiest " },
    { "type": "variable", "value": "player2" },
    { "type": "text", "value": " om te drinken." }
  ]
}
```
→ *"Tim kiest Emma om te drinken."* — never the same person twice.

**Everyone**

```json
{
  "type": "vote",
  "participants": "all",
  "content": [
    { "type": "variable", "value": "all_players" },
    { "type": "text", "value": " wijzen tegelijk naar wie het laatst te laat kwam." }
  ]
}
```
→ *"Tim, Lisa, Mark en Emma wijzen tegelijk naar wie het laatst te laat kwam."*

**No variables at all** — perfectly valid:

```json
{
  "type": "custom",
  "participants": 1,
  "content": [{ "type": "text", "value": "Nieuwe regel: niemand mag namen gebruiken." }]
}
```

---

## 6. Writing a deck that works

- **Mind the participant count.** It is the single most common mistake. A card
  using `player2` needs `"participants": 2` or higher.
- **Put spaces in the text segments**, not around the variables.
- **Vary the participant counts.** A deck where every card needs four players is
  unplayable by three people — the engine filters those cards out, and a deck
  with none left refuses to start.
- **Prefer ranges to fixed amounts** for replay value.
- Keep card text to one or two sentences; it is read aloud from a phone.

---

## 7. Importing

Signed-in users with the deck creator import from **My decks → Import deck**.
Administrators import base-game decks from the admin panel.

An imported deck always lands **private and unpublished**, owned by whoever
imported it, no matter what the file says. Visibility is a separate, deliberate
action.

Files are capped at 2 MB and validated before anything is written, so a rejected
import leaves no partial deck behind.

---

## 8. Versioning

`version` is `1`. A future version will change it and be accepted alongside this
one; decks written to this document will keep importing.
