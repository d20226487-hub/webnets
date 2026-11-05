You are rebuilding every page of the **Vantro Australia** template, a landing site (angle: matchday). Keep the section order, the content and every rule in the brief. Write the page text exactly as given (it is in English); write code and comments in English. If something in the brief is missing or unclear, ask instead of guessing, and never invent claims, figures, offers or reviews.

**Target stack:** WordPress with the SX ACF blocks (`sx/<block>` from `wp/sx-blocks.php`). If you use another stack (plain HTML/CSS, React/Next.js, another CMS), build one component per section with the listed fields as props.

## Acceptance checklist

- [ ] Sections appear in the order given, with the same layout variant (or an equivalent layout).
- [ ] All text is used exactly as given; nothing invented (no extra claims, figures, offers or reviews).
- [ ] One H1 per page; headings do not skip levels.
- [ ] No horizontal scrolling at 375px; tap targets at least 44px.
- [ ] Each page is under 64 KB of HTML and CSS (images excluded).
- [ ] The structured data listed for each page is present and valid.
- [ ] Every internal link opens an existing page.
- [ ] Text contrast meets WCAG AA, focus is visible, images have alt text.
- [ ] Every rule in the market guidance and the block rules holds on every page.

---

# Rebuild brief: Vantro Australia

_Follow the system prompt (role, target stack, acceptance checklist) together with this brief._

## Site type guidance

- Purpose: sell one brand on one page and send the visitor to sign up. Every section leads to the same primary CTA.
- Keep exactly one primary CTA per section; the hero CTA must be above the fold at 375x667.
- The hero headline is at most 60 characters, the subtitle at most 160.
- Trust bar values (licence, payout time, payments) come from brand and market data; never type them by hand.
- Product sections (odds board, game strip, bonus banners) only show products the market allows.
- The sticky CTA appears on phones after the hero scrolls out of view.

## Market guidance

### Market context

- Market: Australia (`au`), languages en, currency AUD
- Minimum age: 18+
- Products allowed: sports; not allowed: casino
- Promotions allowed: none; restricted: welcome_bonus, deposit_bonus, free_bets, promo_code
- Payment methods: payid, bpay, visa-debit, mastercard-debit, apple-pay
- App install paths: iOS app-store, Android play
- Notes: Online casino is prohibited under the Interactive Gambling Act. Credit cards cannot be used for online wagering (from June 2024). Inducement advertising is restricted in several states.

### Mandated texts (from the market settings)

- Age notice: Gambling is for adults 18+ only.
- Self-exclusion: Exclude yourself from all licensed online wagering with BetStop, the National Self-Exclusion Register: betstop.gov.au
- Helpline: For free and confidential support call 1800 858 858 or visit gamblinghelponline.org.au

### Rules you must not break

- Every page opens with an age gate (18+) before any gambling content.
- Every page has the responsible-gambling panel (usually in the footer) with the market text: age notice, licence line, taglines, self-exclusion and helpline where given. Never type this text yourself; it comes from the market settings.
- Every page links BetStop, the national self-exclusion register, and the helpline.
- Credit cards are never shown as a payment method.
- No online casino content anywhere (Interactive Gambling Act).

## Site context

- Site: Vantro Australia, brand or publisher: Vantro
- Site type: landing, angle matchday; language `en`, text direction `ltr`

## Design tokens

Fonts (Google Fonts): Oswald, Roboto Condensed.

Use these CSS custom properties (they are the whole palette, type scale and radius set):

```css
:root {
  --c-bg: #0C1726;
  --c-surface: #13223A;
  --c-surface-2: #1A2D4A;
  --c-text: #F4F7FB;
  --c-muted: #9FB0C8;
  --c-border: #24395A;
  --c-accent: #FF6A13;
  --c-on-accent: #0C1726;
  --c-accent-2: #2EC5FF;
  --c-success: #1F9D55;
  --c-warning: #D98B00;
  --c-danger: #D93636;
  --c-hero-text: #F4F7FB;
  --c-hero-muted: #B5C3D6;
  --c-hero-accent: #FF6A13;
  --c-hero-on-accent: #0C1726;
  --hero-bg: linear-gradient(115deg,#0C1726 45%,#16325A 100%);
  --f-display: "Oswald",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --f-body: "Roboto Condensed",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --f-mono: "Roboto Condensed",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
  --fw-heading: 700;
  --fw-body: 400;
  --fw-strong: 500;
  --tt-heading: uppercase;
  --ls-heading: 0.01em;
  --lh-body: 1.5;
  --lh-heading: 1.08;
  --r-sm: 2px;
  --r: 4px;
  --r-lg: 8px;
  --r-btn: 4px;
  --bw: 2px;
  --density: 0.95;
  --fs-0: 12.31px;
  --fs-1: 16px;
  --fs-2: 27.04px;
  --fs-3: clamp(23.9px,19.08px + 1.339vw,35.15px);
  --fs-4: clamp(31.07px,24.81px + 1.741vw,45.7px);
  --fs-5: clamp(40.4px,32.25px + 2.263vw,59.41px);
  --fs-6: clamp(52.52px,41.92px + 2.942vw,77.23px);
  --sp-1: 3.8px;
  --sp-2: 7.6px;
  --sp-3: 11.4px;
  --sp-4: 15.2px;
  --sp-5: 22.8px;
  --sp-6: 30.4px;
  --sp-7: 45.6px;
  --sp-8: 60.8px;
  --sp-9: 91.2px;
  --card-bg: #13223A;
  --card-border: transparent;
  --card-shadow: 0 8px 0 rgba(0,0,0,.35);
  --surface-style: elevated;
}
```

Layout is mobile-first: it must work from 375px to 1440px wide.

## Block rules

- Responsible gambling: All copy comes from markets/<id>.json; never type compliance text into a page.
- Responsible gambling: Every page renders a 'panel' (usually inside the footer). 'strip' is an optional top-of-page addition.
- Responsible gambling: Where a market lists several mandated taglines, one is chosen per site by seed.
- Header: One-page landing and app pages use 'minimal' (no nav) so attention stays on the conversion.
- Header: The CTA stays visible at every width; on mobile the nav collapses into a menu, the CTA does not.
- Hero: Exactly one primary CTA. The secondary action is a text link or ghost button.
- Hero: Primary CTA must sit above the fold at 375x667.
- Hero: Headline 60 characters max; subtitle 160 max (long Russian/Portuguese strings included).
- Hero: scoreboard: the match card is an illustration and must carry a note saying odds are examples.
- Trust bar: Values come from brand facts and market config; nothing is typed by hand.
- Trust bar: Rating shows only when the brand has facts.rating_source.
- Trust bar: Sits directly under the hero (section 2 or 3).
- Features: 3 or 4 items. Titles 4 words max, text one sentence (about 120 characters).
- Features: Features describe the product, never an offer or bonus.
- Showcase: Odds and fixtures are illustrations; the note saying so is required.
- Showcase: Never placed directly after the hero (blueprint order rule).
- Steps: Exactly 3 steps; numbers are used because the order is real.
- Steps: Mention verification and deposit limits honestly; do not promise instant approval.
- FAQ: 4-6 questions: legality in the country, deposits/withdrawals, payout time, verification, limits or self-exclusion.
- FAQ: Answers are plain text, 1-3 sentences. No CTA links inside answers.
- Final CTA: Always the last section before the footer.
- Final CTA: Headline 40 characters max. No urgency claims (timers, 'last chance').
- Footer: Always renders the compliance panel; it cannot be switched off.
- Footer: Payment methods come from the market config so they are always locally correct.
- Age gate: Age comes from the market (21 in KZ, 18 in AU/BR). Never hard-code it.
- Age gate: Content stays in the HTML for crawlers; the overlay is shown by script, so no-JS users still see the compliance footer.
- Sticky CTA: Phones only (860px and narrower). Never covers the compliance panel: the footer gets bottom padding.
- Sticky CTA: Shows the market age badge next to the CTA.

## Page

- Title tag: Vantro: live footy, cricket and basketball odds
- Meta description: Same-game multis, live streaming and PayID withdrawals in about an hour. Licensed in Australia. 18+.
- The only H1: Footy, cricket and hoops. Live odds in one tap.
- Structured data (JSON-LD): WebPage, FAQPage

### Sections, in order

1. **Responsible gambling** — block `sx/compliance-bar`, variant `strip`, role `compliance`
   Purpose: Market-driven responsible gambling notice: age, licence line, taglines, self-exclusion, helpline.
   - `show_verify`: false
2. **Header** — block `sx/header`, variant `minimal`, role `header`
   Purpose: Site header with logo, navigation and primary CTA.
   - `note`: "Licensed in Australia"
   - `sticky`: false
   - `show_cta`: true
3. **Hero** — block `sx/hero`, variant `scoreboard`, role `hero`
   Purpose: Above-the-fold hero: headline, one primary CTA, trust row and an optional offer/feature card.
   - `eyebrow`: "Round 14 is live"
   - `title`: "Footy, cricket and hoops. Live odds in one tap."
   - `subtitle`: "Build same-game multis in seconds, watch selected matches live and withdraw to PayID in about an hour."
   - `secondary`: `{"label":"See how it works","url":"#how"}`
   - `show_trust`: true
   - `match`: `{"league":"Premiership · Round 14","clock":"Q3 08:12","home":{"name":"Harbour Hawks","short":"HAW","color":"#1F6FEB","score":"54"},"away":{"name":"Westside Rams","short":"WSR","color":"#C62839","score":"47"},"markets":[{"label":"Head to head","odds":[{"pick":"HAW","price":"1.62"},{"pick":"WSR","price":"2.30"}]},{"label":"Line","odds":[{"pick":"HAW −6.5","price":"1.90"},{"pick":"WSR +6.5","price":"1.90"}]},{"label":"Total points","odds":[{"pick":"Over 160.5","price":"1.87"},{"pick":"Under 160.5","price":"1.93"}]}],"note":"Example markets for illustration. Live prices change during play."}`
   - `art`: "rings"
4. **Trust bar** — block `sx/trust-bar`, variant `strip`, role `trust`
   Purpose: Licence, payout time, years operating, local payments and (only if sourced) rating.
   - `items`: `["licence","payout","since","payments"]`
5. **Features** — block `sx/features`, variant `grid`, role `content`
   Purpose: 3-4 product reasons to join, each with an icon, a short title and one sentence.
   - `eyebrow`: "Why Vantro"
   - `title`: "Built for match day"
   - `items`: `[{"icon":"layers","title":"Same-game multis","text":"Combine head to head, line and player markets from one match into a single bet."},{"icon":"play","title":"Live streaming","text":"Watch selected matches inside the app while you bet in-play."},{"icon":"bolt","title":"PayID withdrawals","text":"Most withdrawals land in about an hour, seven days a week."},{"icon":"gauge","title":"Limits you control","text":"Set daily, weekly or monthly deposit limits in two taps and change them any time."}]`
6. **Showcase** — block `sx/showcase`, variant `odds-board`, role `content`
   Purpose: Shows the product instead of describing it: an odds board (sports) or game strip (casino).
   - `eyebrow`: "Tonight"
   - `title`: "Markets open right now"
   - `tabs`: `[{"label":"Footy"},{"label":"Cricket"},{"label":"Basketball"},{"label":"Racing"}]`
   - `columns`: `[{"label":"Home"},{"label":"Away"}]`
   - `rows`: `[{"when":"Q3","live":true,"competition":"Premiership","home":"Harbour Hawks","away":"Westside Rams","odds":[{"price":"1.62"},{"price":"2.30"}],"more":148},{"when":"19:40","competition":"Premiership","home":"Coastal Kings","away":"Northern Blues","odds":[{"price":"1.85"},{"price":"1.95"}],"more":132},{"when":"20:10","competition":"T20 Bash","home":"Southern Strikers","away":"City Comets","odds":[{"price":"2.05"},{"price":"1.78"}],"more":96},{"when":"21:00","competition":"National Basketball","home":"Bay Breakers","away":"Valley Stallions","odds":[{"price":"1.50"},{"price":"2.60"}],"more":74}]`
   - `note`: "Fixtures and prices are examples for illustration."
7. **Steps** — block `sx/steps`, variant `row`, role `content`
   Purpose: Three real steps from sign-up to first bet, followed by the mid-page CTA.
   - `eyebrow`: "Getting started"
   - `title`: "Start in three steps"
   - `items`: `[{"title":"Create your account","text":"Name, date of birth and address. Most accounts are verified within minutes."},{"title":"Deposit with PayID","text":"Minimum A$10. You will be asked to set a deposit limit while you are there."},{"title":"Place your first bet","text":"Pick a market, add it to your bet slip and confirm."}]`
   - `show_cta`: true
8. **FAQ** — block `sx/faq`, variant `accordion`, role `content`
   Purpose: Answers the objections that stop sign-ups. Emits FAQPage structured data.
   - `items`: `[{"q":"Is Vantro legal in Australia?","a":"Yes. Vantro holds an Australian wagering licence and offers sports and racing betting, which is permitted online. Online casino games are not offered in Australia."},{"q":"How fast are withdrawals?","a":"Most PayID withdrawals arrive in about an hour. Bank transfers can take up to one business day."},{"q":"Which payment methods can I use?","a":"PayID, BPAY, Visa and Mastercard debit cards and Apple Pay. Credit cards cannot be used for online betting in Australia."},{"q":"Do I need to verify my identity?","a":"Yes. Australian law requires identity checks before you can bet. Most accounts are verified automatically within minutes."},{"q":"How do I set limits or take a break?","a":"Set deposit limits in your account settings at any time. You can also exclude yourself from all licensed betting providers through BetStop at betstop.gov.au."}]`
9. **Final CTA** — block `sx/cta-band`, variant `solid`, role `content`
   Purpose: The last push before the footer: short headline, the CTA and two proof points.
   - `title`: "Round 14 is on."
   - `text`: "Join in about two minutes and set your limits from day one."
   - `show_code`: false
10. **Footer** — block `sx/footer`, variant `compact`, role `footer`
   Purpose: Footer with links, payment methods, the compliance panel and copyright.
   - `show_verify`: false
  - inside it: **Responsible gambling** — block `sx/compliance-bar`, variant `panel`
     - `show_verify`: false
11. **Age gate** — block `sx/age-gate`, variant `modal`, role `age-gate`
   Purpose: Age confirmation overlay using the market's minimum age. Remembers the answer for 30 days.
   - `leave_url`: "https://www.google.com"
   - `preview`: false
12. **Sticky CTA** — block `sx/sticky-cta`, variant `bar`, role `sticky`
   Purpose: Bottom CTA on phones. Appears once the hero CTA scrolls away; hides while the final CTA is on screen.

