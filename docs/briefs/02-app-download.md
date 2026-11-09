# Brief 02: One-page brand app download

Status: **approved and built** · Phase 2 · Site type id: `app` · Dev guide: [docs/types/app.md](../types/app.md)

## 1. Job of the page

A single-brand, single-page site whose only goal is getting the brand's app installed.
The visitor has already decided they want the app; the page removes friction and doubt.

| | |
|---|---|
| Primary conversion | Tap on the right store badge, or the APK download, for the visitor's device |
| Desktop conversion | Scan the QR code with the phone (the page cannot install anything on a desktop) |
| Primary KPI | Install-link CTR by platform (iOS, Android store, APK) |
| Secondary KPIs | QR scans (tracked link on the QR), APK guide opens, bounce rate on Android |
| Traffic | "vantro app", "nyxa apk", "nyxa скачать", "baixar app marlowe", app-review referrals |
| Visitor mindset | "Is this the official app? Is it safe? Will it work on my phone? How do I install it?" |

## 2. How it differs from the landing type

The landing page sells the brand. This page sells **the install**: the brand is already chosen.
So the page should feel like a **store listing**, the pattern visitors already trust: app icon, name, publisher,
size, version, age rating, screenshots, what's new, permissions. It must **resemble the structure** of a store page
without **imitating Apple's or Google's interface or branding** (no fake App Store chrome, no fake star widgets).
Only the official badges are used, as each store's badge guidelines allow.

## 3. Distribution differs by market (needs confirming)

The page has three install paths. Which ones a site shows comes from the market config plus the brand's links.

| Path | What the visitor does | Page support needed |
|---|---|---|
| App Store (iOS) | Tap badge → store | Badge, optional Smart App Banner meta tag |
| Google Play | Tap badge → store | Badge |
| APK (Android, direct) | Download file → allow install → open | Step-by-step guide, file size, version, safety notes |

My current assumptions, to confirm with the team (operators' real availability varies by brand):

| | KZ | AU | BR |
|---|---|---|---|
| iOS | App Store | App Store | App Store |
| Android | **APK** (common for KZ bookmakers) | Google Play | Google Play and/or APK |
| Default angle | `apk-easy` | `official-app` | `official-app` |

All rules from brief 01 still apply: no offers in KZ, AU or BR; age gate and compliance panel; no casino on KZ or AU sites.

## 4. Conversion rules

1. **Device-aware primary CTA.** iPhone visitors see the App Store badge first, Android visitors see Play or APK first,
   desktop visitors see the QR code first. Without JavaScript, all paths show, in market order.
2. **Install path above the fold** at 375×667, with "Official app by {brand}" and the licence line next to it.
3. **Separate tracked links per platform** (`links.ios`, `links.android`, `links.apk`). Every badge and button carries
   `data-cta=<position>` and `data-platform=<ios|android|apk>`.
4. **Facts before persuasion.** Version, size, minimum OS, languages and last update are visible without scrolling on desktop.
   These come from the site config, never invented.
5. **APK honesty.** If the page offers an APK: explain why it is not in Google Play (store policy in that market),
   show the file size and version, and say the visitor's phone will warn them and why.
6. **No dead ends.** Every section ends in, or sits next to, an install action. The sticky bar on phones is an install bar
   (icon + name + one button), similar to an app banner.
7. **Fast.** Same 60 KB HTML+CSS budget as landings. Screenshots are lazy-loaded except the first.

### Anti-patterns (never)

- Imitating App Store or Google Play UI (their header, star widget, "GET" pill styling, fonts) or implying the page *is* the store
- Fake or unsourced ratings, download counts ("1M+ downloads") or review quotes
- "Download now before it's gone" or other urgency
- Auto-starting a download on page load
- An APK link to anything other than the brand's own domain or tracked link
- Bonus offers for installing (also restricted in all three markets)

## 5. Section blueprint

`R` required, `O` optional, `S` seeded order within the middle zone.

| # | Section | Rule | Purpose | Variants planned |
|---|---|---|---|---|
| – | Age gate, compliance strip | R / AU | as landing | existing |
| 1 | Header | R, locked | App icon + name + one Get button | **app-bar** (new), minimal |
| 2 | App hero | R, first | Icon, name, publisher, fact row, install buttons, phone | **listing** (store-page layout), **device** (big phone + copy), **qr-first** (desktop-led, QR beside phone), **banner** (accent band, copy centred, phone rising from the bottom edge), **stage** (phone on a gradient panel at the start, copy and fact chips beside it), **catalog** (download catalog: app card, stats row, big download panel, screenshot thumbnails), **apk-page** (header row with the download button on the end side, then a row of five figures), **hub** (header with an official-version chip and the version, the description; a large download button over a specifications box with a screenshot). The job form's Design option can pick catalog, apk-page or hub |
| 3 | Screenshots | R | Show the app | **carousel** (scroll-snap), **fan** (3 phones overlapping), **grid** |
| 4 | Features | R, middle, S | 3–4 app-specific reasons | existing: grid, rows, bento |
| 5 | Install guide | R if APK, else O; middle, S | How to install, per platform | **tabs** (iOS / Android / APK), **side-by-side** |
| 6 | App info | R, middle, S | Version, size, OS, languages, updated, permissions | **table**, **chips** |
| 7 | What's new | O, middle, S | Latest release notes | **changelog** |
| 8 | Trust | R | Licence, payout, since; "official app" | existing trust-bar |
| 9 | FAQ | R, last third | Safety, APK, updates, storage, devices | existing |
| 10 | Final install band | R, before footer | Badges + QR | **download panel** (new; badges + QR + facts) |
| – | Footer | R, compact | | existing |
| – | Sticky install bar | R on phones | Icon, name, one button for the detected platform | **app-banner** (new variant of sticky-cta) |

New blocks: `app-hero`, `screenshots`, `install-guide`, `app-info`, `changelog`, `download-panel`.
Extended: `header` (+app-bar), `sticky-cta` (+app-banner), CTA helper (per-platform links).

## 6. Angles

| Angle | Leads with | Hero formula | Optional on | Good presets |
|---|---|---|---|---|
| `official-app` | Trust: official, licensed, safe | "The official {brand} app for {country}" | what's new | fresh-minimal, clean-sportsbook, editorial |
| `live-in-pocket` | Live betting, streaming, notifications | "Every match in your pocket" | what's new | midnight-neon, arena, terminal |
| `apk-easy` | Simple Android install | "Install {brand} on Android in 3 steps" | install guide forced on, side-by-side | terminal, sunburst, clean-sportsbook |
| `fast-app` | Payments in the app (Kaspi, PIX, PayID) | "Deposit and withdraw in the app in minutes" | app info chips | candy-pop, sunburst, fresh-minimal |

## 7. Technical decisions

- **Device detection:** a few lines of JS reorder the install buttons and set the sticky bar's platform. No JS = all paths visible.
- **QR code:** generated at **build time as inline SVG** (no external QR service, no tracking leak). Needs a QR encoder
  in PHP; see question 2.
- **Smart App Banner:** optional `<meta name="apple-itunes-app" content="app-id=…">` when the config has an App Store ID.
- **Screenshots:** real projects supply images (`screenshots[].src`, WebP/AVIF, 9:19.5). Demos draw **CSS phone screens**
  from the preset tokens, so no stock images are needed. One `image` slot per screen is ready for real assets.
- **Schema.org:** `MobileApplication` (name, operatingSystem, applicationCategory, softwareVersion, fileSize), plus `FAQPage`.
  No `aggregateRating` unless the brand has a sourced rating.
- **App facts** live in a new `app` section of the brand file (version, size, min OS, languages, updated, store IDs)
  so they are written once per brand, not per site.

## 8. Reference patterns

From general industry knowledge (not live teardowns):

- **App Store / Google Play product pages:** fact row (rating, age, size), screenshots first, "What's new", data & permissions. We borrow the information order, not the look.
- **Operator app pages (bet365, Sportsbet, DraftKings style):** big phone mockup, both badges, QR for desktop, short feature list.
- **KZ and other APK markets:** numbered install steps with screenshots of the Android warning dialogs; file size and version shown next to the button.
- **Fintech app pages (Revolut, Nubank, Monzo style):** device-led hero, QR "scan to download" on desktop, one-line trust signals.

## 9. Demos planned

| Demo | Brand | Market | Preset | Angle | Hero | Android path |
|---|---|---|---|---|---|---|
| A | Vantro | AU (EN) | fresh-minimal | official-app | listing | Google Play |
| B | Nyxa | KZ (RU) | midnight-neon | apk-easy | device | APK |
| C | Marlowe Club | BR (PT) | velvet-club | fast-app | qr-first | Google Play + APK |

## 10. Acceptance criteria for Demo A

- [ ] All rules in section 4 hold at 375 px and 1440 px; iPhone, Android and desktop each see the right primary action
- [ ] Builds from `"type": "app"` with no warnings; content validated against fields.json
- [ ] No App Store / Google Play interface imitation (badges only)
- [ ] Page passes the 60 KB budget; QR is inline SVG and scans correctly
- [ ] RU and PT UI strings fit at 375 px

## 11. Open questions for you

1. **Distribution:** are the assumptions in section 3 right (APK in KZ, Play in AU and BR)? Do any clients ship APK-only?
2. **QR encoder:** may I add one small, MIT-licensed PHP library via Composer (for example `chillerlan/php-qrcode`),
   or should I write a minimal built-in encoder so the system has no dependencies?
3. **Screenshots:** will clients supply real app screenshots? Until then, demos use CSS-drawn phone screens.
4. **Store badges:** do you have the official badge files from clients, or should demos use neutral text buttons
   ("Download on the App Store" style text, no logos) until the real badge assets are supplied?
