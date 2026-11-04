# Site type: one-page brand app download (`app`)

Status: **Phase 2 complete.** Brief: [docs/briefs/02-app-download.md](../briefs/02-app-download.md) ·
Blueprint: [blueprints/app.json](../../blueprints/app.json) · Schema: `dist/schemas/app.schema.json`

Reference demos (fictional brands):

| Demo | Config | Review page |
|---|---|---|
| A · Vantro · AU · EN · App Store + Play | [sites/demos/app-a-vantro-au.json](../../sites/demos/app-a-vantro-au.json) | https://claude.ai/artifact/Sp51ZgtUL2ia8m9WgQKMy8 |
| B · Nyxa · KZ · RU · App Store + APK | [sites/demos/app-b-nyxa-kz.json](../../sites/demos/app-b-nyxa-kz.json) | https://claude.ai/artifact/T6UfHqFvRx7PwS414xBQ8W |
| C · Marlowe Club · BR · PT · App Store + Play + APK | [sites/demos/app-c-marlowe-br.json](../../sites/demos/app-c-marlowe-br.json) | https://claude.ai/artifact/A314gvyNJFt2SDetdFJVXL |

## 1. What the page is for

The visitor already wants the app. The page gets it installed on **their** device with as little doubt as possible.
It follows a store listing's information order (identity, facts, install, screenshots, what's new) in our own visual
language. It never imitates the App Store or Google Play interface; only install buttons with neutral styling
(official badge files can replace them when clients supply them).

## 2. Where the data comes from

| Data | Lives in | Written per |
|---|---|---|
| App name, developer, version, sizes, minimum OS, languages, age rating, App Store ID, release notes | `brands/operators.json` → `<brand>.app` | brand (once) |
| Which install paths a market uses (`app-store`, `play`, `apk`, `both`) | `markets/<id>.json` → `apps` | market (once) |
| Tracked links per platform: `links.ios`, `links.android`, `links.apk`, `links.qr` | site config | site |
| Headline, features, screen captions, FAQ | site config `content` | site |
| Install steps | `i18n/strings.json` (`install.*`) | nobody (built in); override per site only if needed |

A site shows an install path only when **all three** agree: the market uses it, the brand offers it, and the site has
the link. Otherwise the build warns (see section 5).

## 3. Build an app page by hand

1. Copy [sites/templates/app.json](../../sites/templates/app.json) to `sites/<project>/<id>.json`.
2. Check the brand's `app` block in `brands/operators.json` is current (version, sizes, release notes).
3. Set `brand`, `market`, `preset`, `angle`, a unique `seed`; keep `"prefix": "auto"`.
4. Add a link for every path the market uses. In Brazil all links must be on the brand's `.bet.br` domain.
   `links.qr` should be a device-aware smart link (it is what desktop visitors scan).
5. Fill `content`, remove every TODO.
6. `php build/build.php sites/<project>/<id>.json`. **A build with warnings is not shippable.**
7. `php build/diversity.php sites/<project>/*.json`
8. Scan the QR on the built page with a phone, then run the [QA checklist](../qa-checklist.md).

Content pipelines: target `dist/schemas/app.schema.json` (`php build/config-schema.php app`), and follow the generator
rules in [the landing guide](landing.md#3-build-landing-sites-automatically). Never generate app facts, install steps,
compliance text or ratings.

## 4. Page anatomy

| Section id | Block | Rule | Variants | Notes |
|---|---|---|---|---|
| (strip) | compliance-bar | required in AU | strip | |
| header | header | required | app-bar | App icon, name, one "Get" button pointed at the visitor's store |
| hero | app-hero | required, first | listing, device, qr-first | Platform script lives here: reorders install buttons, sets `data-platform` |
| trust | trust-bar | required | strip, badges | |
| screens | screenshots | required | carousel, fan, grid | 3–6 screens; CSS-drawn until real screenshots exist (`image`) |
| features | features | required, middle | grid, rows, bento | App-specific reasons, not brand marketing |
| install | install-guide | **required when the site offers an APK**, absent otherwise | side-by-side, tabs | APK path always shows version, size, distribution note |
| info | app-info | required, middle | table, chips | All values from brand data |
| whatsnew | changelog | optional, middle | list | Skipped with a warning if the brand has no notes in the locale |
| faq | faq | required, bottom | accordion, columns | Cover: free?, devices, official/safe, APK (if any), limits |
| final | download-panel | required, last | panel | All install buttons + QR (desktop only) |
| footer, age-gate | | required | compact; modal, fullscreen | |
| sticky-cta | sticky-cta | required | app-banner | Icon, name, age, "Get" for the visitor's platform |

Screen kinds for drawn phones: `home`, `live`, `slip`, `wallet`, `casino`, `table` (live dealer), `install` (Android dialog).

| Angle | Optional on | Hero options | Notes |
|---|---|---|---|
| official-app | what's new | listing, device | Default; trust-led |
| live-in-pocket | what's new | device, listing | Sports markets only |
| apk-easy | (install is already on) | device, listing | Only when the site offers an APK |
| fast-app | — | qr-first, listing | Payments-led (Kaspi, PIX, PayID) |

## 5. Guardrails the code enforces

| Guardrail | Where |
|---|---|
| Visitor's platform first; header/sticky "Get" links to the right store | `sx_platform_script()` |
| No-JS visitors still see every install path in market order | buttons are plain links |
| Per-platform tracked links with `data-cta` + `data-platform` | `sx_install()`, `sx_app_get()` |
| Warn when a path is missing a link, when Android visitors have no path at all, or when the brand has no path in the market | `sx_app_platforms()` |
| Install guide forced on when an APK is offered; APK honesty note always shown | blueprint rule `apk`, install-guide block |
| App facts never come from content | app-hero, app-info, install-guide read `brands/*.app` |
| QR generated at build time, no external service; stored once per page | `lib/qr.php` (`php build/test-qr.php`) |
| QR hidden on phones | `[data-platform]` + media query |
| iOS Smart App Banner when the brand has an App Store ID | `lib/page.php` |
| `MobileApplication` + `FAQPage` structured data, no ratings | app-hero, faq |

Check by hand: the QR scans, the store links open the right listing, the APK URL serves the current version.

## 6. Diversity

Same rule as landings: two sites in a project must differ in preset, angle and hero variant.
With three hero variants, **a project can hold 3 app pages that pass**. The demos sit at 30–56% overlap.
Adding a fourth hero layout (for example a video-led or comparison-of-devices hero) is the first item to raise that cap.

## 6b. Platform and look options

Two config options stretch one app template further than a preset does:

| Option | Values | What changes |
|---|---|---|
| `platform` | omit (both), `ios`, `android` | A single-platform site keeps only that platform's install paths (Android keeps Google Play and APK), app size, OS line and JSON-LD. Write the copy for that platform in the config ("Vantro for iPhone"). |
| `look` | omit (preset), `neutral`, `brand`, `play`, `appstore` | Token overrides on top of the preset (`tokens/looks.json`; `brand` is built from the brand colour) plus a few look rules (`tokens/looks/<look>.css`): button shape and labels, app-icon corners, panels. `play` leads with the Google Play button. |

**Store layouts:** `play` and `appstore` also replace the section list (blueprint `layouts`), so the page reads like
that store's listing:

| Look | Sections, in order |
|---|---|
| `play` | store header (name, developer, rating · downloads · age, Install) · screenshots · about this app (tags, facts) · data safety · ratings and reviews · what's new · FAQ · download panel |
| `appstore` | store header (icon, subtitle, rating, Get, six-cell info strip) · what's new · preview · description · ratings & reviews · app privacy · information · FAQ · download panel |

New blocks: `store-header`, `store-about`, `app-privacy`, `store-reviews`. Store figures (rating, rating count, 5-to-1
split, downloads) come from `brands/operators.json` `app.store.<ios|android>` and must be copied from the real listing
(`checked_at`); a single-platform page always shows its own store's figures. Privacy types come from `app.privacy` and
must match the store's privacy label. Review cards reuse `brands/reviews/<brand>.json`; seeded reviews need `"demo": true`
and carry a sample label.

**Variation demo:** a config with a `variations` block (each option is a config override) builds every combination with
`php build/variations.php sites/demos/app-b-nyxa-kz.json`. Each page gets a demo bar on top that links the 15
variations (3 platforms × 5 looks). Publish `dist/review/<id>.html` as the page and `dist/review/<id>/*.html` through `files`.
App demo B (Nyxa, KZ, Russian; Android = APK): https://claude.ai/artifact/T6UfHqFvRx7PwS414xBQ8W. Switchers live on the Russian demo of each type for now.

## 7. Known gaps and next steps

- QR codes not yet scanned with a real phone (encoder passes all spec reference tests).
- Distribution per market (`markets/*.json` → `apps`) is an assumption; confirm per brand.
- Install buttons are neutral; swap in official App Store / Google Play badge files when clients provide them.
- Real screenshots: set `image` per screen (WebP/AVIF, 9:19.5). Drawn screens are demo-only.
- A fourth hero variant to raise the per-project cap above 3.
