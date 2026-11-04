# Brief 01: One-page branded landing

Status: **approved and built** · Phase 1 · Site type id: `landing` · Dev guide: [docs/types/landing.md](../types/landing.md)

## 1. Job of the page

A single-brand, single-page site that takes a visitor who already has some interest in the brand
and sends them to the brand's registration page through the tracked link.

| | |
|---|---|
| Primary conversion | Click on any brand CTA (all CTAs go to the same tracked URL) |
| Primary KPI | Outbound CTR (unique CTA clicks ÷ unique visitors) |
| Secondary KPIs | Share of clicks from the hero CTA, scroll depth to the final CTA, bounce rate |
| Traffic | Brand + modifier searches ("vantro app", "vantro login", "vantro saque pix"), referral links, social |
| Visitor mindset | "Is this brand legit, what do I get, how fast can I start?" |

## 2. What changes because of our markets

All three demo markets restrict welcome bonuses, so **this page cannot sell an offer**. It sells the
product instead: odds, live betting, fast payouts, local payments, the app, the licence.
That is also how operators advertise in Australia today, where inducements are restricted.

| | KZ | AU | BR |
|---|---|---|---|
| Verticals | Sports only | Sports only | Sports + casino |
| Age | 21+ | 18+ | 18+ |
| Lead proof points | Kaspi withdrawals, live odds, licence | Same-game multis, fast withdrawals, BetStop-compliant | PIX withdrawals, .bet.br licence, live |
| Language | RU default, KK optional | EN | PT |
| Extra | Optional RU/KK switch in header | One mandated tagline on the page | Brand link must point to the .bet.br domain |

## 3. Conversion rules (guardrails for every variant)

1. **One goal.** No navigation menu, no links to other brands, no outbound links except the brand CTA and compliance links.
2. **CTA above the fold** at 375×667, with a trust signal (licence, rating or payout time) within one line of it.
3. **CTA cadence.** The CTA appears in the hero, at least once mid-page, and in a final CTA band. On mobile, a sticky bottom CTA appears once the hero CTA scrolls out of view.
4. **Same label, same destination.** All CTAs use one verb ("Bet now" / "Сделать ставку" / "Apostar agora") and one tracked URL.
5. **Message match.** The hero headline matches the page's *angle* (section 5), which is chosen to match the main query the site targets.
6. **Short.** 6–9 sections. Mobile page length of about 6–9 screens.
7. **Fast.** LCP under 2.0 s on 4G; HTML + CSS under 60 KB; no JS frameworks; JS only for the age gate, sticky CTA and FAQ.
8. **Objections answered on the page:** legality in the country, how to deposit and withdraw, how long withdrawals take, verification (KYC), minimum age.

### Anti-patterns (never)

- Comparison tables or other brands (that is the casino review site type)
- Fake urgency: countdown timers, "only 3 left", "1,284 people joined today". These are also a responsible-gambling risk.
- Bonus amounts, promo codes or "free bets" in KZ, AU or BR
- Long SEO text walls, keyword-stuffed paragraphs
- Pop-ups other than the age gate; autoplay video with sound
- Invented reviews or ratings presented as real. Ratings must come from the content pipeline with a source.

## 4. Section blueprint

`R` = required, `O` = optional (seed decides), `S` = swappable position (seed may reorder within the middle zone).

| # | Section | Rule | Purpose | Variants planned |
|---|---|---|---|---|
| – | Age gate | R (overlay) | Compliance | modal, fullscreen *(done)* |
| – | Compliance strip | O; R in AU | Mandated tagline at the top | strip *(done)* |
| 1 | Header | R, locked to `minimal` | Logo, licence note, CTA. No nav. | minimal, **minimal-split** (logo centre, CTA right), **minimal-lang** (RU/KK switch) |
| 2 | Hero | R, always first | Headline, CTA, trust row, proof card | split, centered *(preview)*, **full-bleed photo**, **scoreboard** (live match card), **stat-led** (one big number: payout minutes) |
| 3 | Trust bar | R, section 2 or 3 | Licence, rating, payout time, year founded, local payments | **strip**, **badges**, **ticker** |
| 4 | Features | R, middle zone, S | 3–4 product reasons to join | **icon grid**, **alternating rows** (image + text), **bento** |
| 5 | Product showcase | O, middle zone, S | Show the product, not describe it | **odds board** (sports), **live match list** (sports), **game strip** (casino, BR only) |
| 6 | How to start | R, middle zone, S | 3 real steps: register, deposit, bet | **horizontal steps**, **vertical timeline**, **phone frames** |
| 7 | Payments | O, middle zone, S | Local methods with time and limits | **logo row**, **table** (method · deposit · withdrawal time) |
| 8 | FAQ | R, last third | Objection handling + FAQPage schema | **accordion**, **two-column** |
| 9 | Final CTA band | R, before footer | Last push | **solid band**, **split with phone**, **minimal line** |
| – | Footer | R, locked to `compact` | Compliance panel, legal links | compact *(done)* |
| – | Sticky mobile CTA | R on mobile | Keeps the CTA reachable | **bar**, **pill** |

Middle-zone order (sections 4–7) is shuffled by seed, with two rules: features come before payments,
and the showcase never sits directly next to the hero.

New blocks: `trust-bar`, `features`, `showcase` (odds-board / match-list / game-strip), `steps`, `payments`, `faq`,
`cta-band`, `sticky-cta`. Extended: `header` (+2), `hero` (+3).

## 5. Angles (content archetypes)

The angle is the second diversity lever after the preset. It decides the headline formula, which optional
sections appear, and which proof point leads. Copy comes from the content pipeline; the angle tells it what to write.

| Angle | Leads with | Hero formula | Optional sections on | Good presets |
|---|---|---|---|---|
| `matchday` | Live odds, big leagues | "[Sport/league] odds, live, in one tap" | odds board, steps | arena, midnight-neon, sunburst |
| `fast-payout` | Withdrawal speed, local rails | "Withdraw to [Kaspi/PIX/PayID] in [n] minutes" | payments table | clean-sportsbook, fresh-minimal, terminal |
| `app-first` | Mobile experience | "Every match in your pocket" | phone-frame steps | fresh-minimal, midnight-neon, candy-pop |
| `trusted` | Licence, years, rating | "Licensed by [regulator]. Paying out since [year]." | trust badges, FAQ two-column | editorial, velvet-club, clean-sportsbook |
| `live-casino` (BR only) | Live dealer tables | "Live tables, real dealers, 24/7" | game strip | luxury-gold, velvet-club |

## 6. Diversity budget

Per site the seed picks: preset (10) × angle (4–5) × hero (5) × trust bar (3) × features (3) × steps (3) ×
FAQ (2) × CTA band (3) × sticky (2) × middle order (~6) × optional sections on/off.
That is over **1 million** structurally different pages before copy, which is far more than any project needs.
The QA rule is simple: two sites in the same project must differ in **preset, hero variant and angle**.

## 7. Technical notes

- Every CTA: `rel="nofollow sponsored noopener"`, `data-cta="hero|mid|final|sticky|header"` for analytics.
- Tracked URL lives in the site config once (`links.go`), never in block props.
- Schema.org: `WebPage` + `FAQPage`. No `AggregateRating` (self-served ratings are not eligible and look spammy).
- Images: one hero image max (full-bleed variant), AVIF/WebP, `fetchpriority="high"`. Other visuals are CSS/SVG.
- Fonts: preset only, 2 families, at most 4 weights.

## 8. Reference patterns

From how operators and strong consumer brands build landing pages (general industry knowledge, not live teardowns):

- **Operator promo landers (bet365, Betway, Stake style):** one dark hero, one big CTA, offer card beside it, terms line under it. We keep the layout and swap the offer for proof points.
- **Australian operators:** lead with product features (multis, live streaming, fast withdrawals) because inducements are restricted. This is the model for all three of our markets.
- **US sportsbook signup pages (DraftKings, FanDuel style):** a three-step "how to start" strip right under the hero. It cuts perceived effort.
- **Fintech landers (Revolut, Nubank style):** one big number as the hero (fees, minutes), phone mockup, trust logos. Basis for `stat-led` and `fast-payout`.
- **Brazilian operators:** PIX logo and "saque em minutos" next to the CTA; licence and .bet.br prominent since 2025.

After you approve the brief I can do a short live look at 3–4 of these pages for layout details, if you want that before Demo A.

## 9. Demos planned

| Demo | Brand | Market | Preset | Angle | Hero |
|---|---|---|---|---|---|
| A | Vantro | AU (EN) | arena | matchday | scoreboard |
| B | Nyxa | KZ (RU, KK switch) | terminal | fast-payout | stat-led |
| C | Marlowe Club | BR (PT) | luxury-gold | live-casino | full-bleed |

A is built first and reviewed before B and C. English first so the whole team can review it.

## 10. Acceptance criteria for Demo A

- [ ] Every rule in section 3 holds at 375 px and 1440 px
- [ ] Lighthouse mobile: performance ≥ 90, accessibility ≥ 95
- [ ] Builds from a site config with `"type": "landing"` and no hand-written HTML
- [ ] Build passes with no warnings
- [ ] Renders correctly in the RU and PT locales (string length check), even though A is an AU site

## 11. Open questions for you

1. **Tracking links:** one URL per site, or different URLs per CTA position (hero / sticky / final)?
2. **Ratings:** do you have a real rating source per brand (app store rating, review site score)? If not, the trust bar shows licence, payout and year only.
3. **KZ language:** do KZ sites need a working RU/KK switch (two pages), or RU only?
4. **Hero imagery:** is stock or AI-generated photography acceptable for the full-bleed hero, or should visuals stay CSS/SVG only?
