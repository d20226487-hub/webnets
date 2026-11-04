# Brief 03: One-page brand bonuses and promo codes

Status: **approved and built** (Demos A, B in the demo-only market; Demo C waits for compliance) · Phase 3 · Site type id: `promo` · Dev guide: [docs/types/promo.md](../types/promo.md)

## 1. Job of the page

A single-brand page for visitors searching for that brand's offers or promo code ("vantro promo code",
"nyxa бонус", "marlowe código promocional"). The page shows what the brand currently offers **in the visitor's market**,
makes claiming it easy, and states the terms plainly.

| | |
|---|---|
| Primary conversion | Click "Claim" on the lead offer (tracked link), with the code copied if there is one |
| Secondary conversion | Copy-code click (tracked as its own event), claims on other listed promotions |
| Primary KPI | Claim CTR on the lead offer |
| Secondary KPIs | Copy-to-claim rate, terms-section opens, bounce rate |
| Visitor mindset | "Is there a code? Is it real and current? What's the catch? How do I use it?" |

## 2. The market problem, and how the type handles it

All three demo markets restrict incentives. As I understand it (to be confirmed by compliance):

| | Welcome bonus | Promo codes | Ongoing promotions (boosts, cashback, loyalty) |
|---|---|---|---|
| KZ | Restricted (gambling advertising is tightly limited) | Restricted | Unclear |
| AU | Restricted (inducement advertising banned or limited in several states) | Restricted | Unclear; existing-customer promotions may be allowed in some states |
| BR | Prohibited (Lei 14.790 bans advance bonuses) | Restricted | Unclear; loyalty programs may be allowed |

So this type cannot be "a bonus page with the bonus removed". It needs an explicit, per-market promotion policy:

```json
"promotions": {
  "welcome_bonus": "restricted",
  "deposit_bonus": "restricted",
  "free_bets": "restricted",
  "promo_code": "restricted",
  "odds_boost": "unknown",
  "cashback": "unknown",
  "loyalty": "unknown",
  "tournaments": "unknown"
}
```

Each value is `allowed`, `restricted` or `unknown` (treated as restricted). Then:

- The page lists **only promotion types marked `allowed`** for the market. Nothing else renders, whatever the content says.
- If a market allows **no** promotion types, the build **fails** for this type (an error, not a warning). The
  satellite should then use another type (landing, app, review) in that market.
- The policy is set by compliance once per market, not by content writers or generators.

Until compliance answers, KZ, AU and BR are all "no types allowed", so this type cannot ship there.

## 3. Where promotion data comes from

Offers expire and change, so they cannot live in page content. Each brand gets a promotion feed:

`brands/promotions/<brand>.json` (maintained by the affiliate team, or later filled from an operator feed):

| Field | Example | Notes |
|---|---|---|
| `id`, `type` | `wb-2026-q4`, `welcome_bonus` | `type` must match a market policy key |
| `markets` | `["xx"]` | Where this promo is valid |
| `title` (per locale) | "100% up to 200 on your first deposit" | Short, factual |
| `value` | `{ "percent": 100, "max": 200 }` or `{ "free_spins": 50 }` | Structured, so it can be formatted per currency |
| `code` | `VANTRO200` or `null` | `null` = no code needed |
| `code_scope` | `exclusive` / `public` | "Exclusive" may only be claimed if it is |
| `terms` | `min_deposit`, `wagering`, `max_bet`, `expiry_days`, `eligible`, `excluded_payments`, `games` | **Significant terms**, shown next to the offer |
| `valid_from`, `valid_to` | dates | Build fails if `valid_to` is past |
| `verified_at` | date | Build warns if older than 14 days |
| `terms_url` | brand link | Full T&Cs |

## 4. Conversion and honesty rules

1. **Lead offer above the fold** at 375×667: value, code (with a copy button), the two most important terms, and Claim.
2. **Significant terms next to the offer**, not only at the bottom: minimum deposit, wagering, expiry, eligibility.
   Full terms one tap away.
3. **One code, shown in full.** No "reveal code" tricks that hide the code behind the click. Copy works on every device
   and falls back to selecting the text.
4. **"Verified on {date}" on every offer**, taken from `verified_at`.
5. **Claim and copy are separate tracked events**: `data-cta="claim"` on the link, `data-cta="copy"` on the button.
6. **Other promotions** (if allowed) are listed below the lead offer in the same format, never above it.
7. **Same CTA rules as landings**: one label ("Claim offer"), tracked links, `rel="nofollow sponsored"`.
8. **Responsible gambling stays prominent**: deposit limits mentioned in how-to-claim; compliance panel as usual.

### Anti-patterns (never)

- Countdown timers or "ends tonight" unless `valid_to` really is today, and even then no ticking clock
- "Exclusive" on a public code; codes or values that are not in the promo feed
- Hidden wagering requirements, or terms only in a collapsed footer
- Click-to-reveal codes and fake "X people used this code today" counters
- Offers to markets where the policy says `restricted` or `unknown`
- Stacked or "best bonus" comparisons with other brands (that is the review-site type)

## 5. Section blueprint

| # | Section | Rule | Purpose | Variants planned |
|---|---|---|---|---|
| 1 | Header | R, locked | Logo, Claim | minimal, minimal-split |
| 2 | Offer hero | R, first | Lead offer: value, code + copy, 2 key terms, Claim, verified date | **code-ticket**, **split-offer** (copy beside a big code card), **centered-code** |
| 3 | Trust | R | Licence, payout, since; "verified on" | existing |
| 4 | Key terms | R, middle, S | All significant terms of the lead offer, plainly | **table**, **checklist** |
| 5 | How to claim | R, middle, S | 3 steps incl. where to enter the code and setting a deposit limit | existing steps (row, timeline) |
| 6 | More promotions | O, middle, S | Other allowed promos from the feed | **cards**, **list** |
| 7 | FAQ | R, bottom | Code not working, eligibility, wagering explained, withdrawal of bonus winnings | existing |
| 8 | Final CTA | R | Code + Claim again | existing cta-band (+ code) |
| – | Footer, age gate | R | | existing |
| – | Sticky | R on phones | Code + copy + Claim | **code-bar** (new sticky variant) |

New blocks: `offer-hero`, `terms`, `promo-list`. New: sticky `code-bar`, copy-code helper, promo feed loader with
expiry/verification checks, market promotion policy.

## 6. Angles

| Angle | Leads with | Requires the market to allow |
|---|---|---|
| `welcome` | First-deposit offer | `welcome_bonus` |
| `code-first` | The promo code itself | `promo_code` |
| `everyday` | Odds boosts, cashback, tournaments | any of `odds_boost`, `cashback`, `tournaments` |
| `loyalty` | VIP / loyalty programme | `loyalty` |

An angle is only available when its promotion type is allowed. The blueprint picks among available angles.

## 7. References (general industry knowledge)

- **Promo-code pages on sports media sites (US style):** code + value + "Claim" in the hero, then terms, then steps. The model for our hero.
- **Coupon sites (RetailMeNot style):** copy-to-clipboard as the main interaction, "verified" dates. We keep copy and verified dates, drop click-to-reveal.
- **UK operator offer pages:** significant terms directly under the offer (regulator expectation there). We adopt this everywhere.

## 8. Demos: needs a decision

None of our three demo markets can show this type until compliance answers. Options:

| Option | What you get | Risk |
|---|---|---|
| **A. Demo-only market** (recommended) | A clearly labelled fictional market `xx` with all types `allowed`, so the full design can be built and reviewed. Never deployable (the build refuses `xx` outside demos). | None, if labelled; it designs the type for markets you add later |
| B. Wait for compliance | Demos in KZ/AU/BR with whatever is actually allowed | Phase 3 blocked; may turn out to be nothing |
| C. Only the "no promotions" outcome | Show that KZ/AU/BR builds fail cleanly | Nothing to review visually |

Recommended plan: Demo A in the demo-only market (`welcome` angle, full offer). Demo B in the demo-only market with
the `everyday` angle. Demo C: whichever of KZ, AU or BR compliance clears first, built from its real policy.

## 9. Acceptance criteria for Demo A

- [ ] Code, value, two key terms and Claim visible at 375×667
- [ ] Copy works (and falls back to text selection); copy and claim are separate tracked events
- [ ] Build fails for an expired promo and for a market with no allowed types; warns for `verified_at` older than 14 days
- [ ] No anti-pattern from section 4 present
- [ ] Under the 60 KB budget; no warnings

## 10. Open questions

1. **Compliance:** for KZ, AU and BR, which of the eight promotion types are allowed on satellite sites?
2. **Markets:** which markets will actually use this type? The demo-only market would mirror the first real one.
3. **Promo data:** who maintains promotions (affiliate managers by hand, or an operator feed/API)?
4. **Codes:** are codes exclusive per satellite (useful for attribution) or brand-wide?
