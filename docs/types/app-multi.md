# Site type: multi-page brand app download (`app-multi`)

Status: **Ready.** Brief: [docs/briefs/02-app-download.md](../briefs/02-app-download.md) (same goal as the one-page app) ·
Blueprint: [blueprints/app-multi.json](../../blueprints/app-multi.json)

Reference demos (fictional brands):

| Demo | Config | Review page |
|---|---|---|
| A · Vantro · AU · EN · App Store + Play | [sites/demos/appm-a-vantro-au.json](../../sites/demos/appm-a-vantro-au.json) | https://claude.ai/artifact/B46DxkwZe2329SLnawxgYB |
| B · Nyxa · KZ · RU · App Store + APK, with switchers | [sites/demos/appm-b-nyxa-kz.json](../../sites/demos/appm-b-nyxa-kz.json) | https://claude.ai/artifact/ArM89qnDMV2ABreBNRKNxL |
| C · Marlowe Club · BR · PT · App Store + Play + APK | [sites/demos/appm-c-marlowe-br.json](../../sites/demos/appm-c-marlowe-br.json) | https://claude.ai/artifact/Qf2j9sSi9NbVhZYRKBp9bi |

## What it is

The one-page app ([app.md](app.md)) as the home page, plus service pages that answer one install question each.
Everything that diversifies the one-page app works here too: hero, screenshot and install variants, the angles, and the
looks (`look`: preset / neutral / brand / play / appstore).

| Page | Slug | When | Sections |
|---|---|---|---|
| home | `/` | always | the one-page app sections + `guides` (links to the other pages, optional) |
| ios | `iphone` | the site offers iOS | page hero, download panel (App Store only), install steps, app info, FAQ, related |
| android | `android` | the site offers Play or APK | page hero, download panel (Android only), APK install guide (APK) or install steps, app info, FAQ, related |
| features | `features` | always | page hero, screenshots, features, text, download panel, related |
| updates | `whats-new` | always | page hero, changelog (all versions from the brand's app data), text, download panel, related |
| help | `help` | always | page hero, FAQ, text (troubleshooting, support, responsible play), related |
| responsible | `responsible-gambling` | required in AU | as on the satellite |

- **Store looks:** `look: "play"` or `"appstore"` turns the home page into that store listing (the same layouts as the
  one-page app, without the `guides` links); the other pages keep the site layout in the store's colours.
- **Platform:** `platform: "ios"` / `"android"` keeps one install path; the other platform's page drops out of the
  site and its nav (page rule `requires_platform`).
- **Per-page context:** a blueprint page can set `ctx` (here `platform_only`), so the iPhone page shows only App Store
  buttons and the Android page only Play / APK buttons.
- **CTA:** app sites send the header and sticky CTA to the brand's app page (`links.go`) with `cta_label` such as
  "Get the app"; the page heroes of pages with a download panel hide their own CTA.

**Variation demo (Russian, KZ):** `php build/export-review-site.php sites/demos/appm-b-nyxa-kz.json` builds platform
(iOS + Android / iOS only / Android only) × look (original / neutral / brand colours / Google Play style / App Store
style): 15 combinations, 80 pages. Review link: https://claude.ai/artifact/ArM89qnDMV2ABreBNRKNxL.
