# QA checklist (every site, before launch)

## Conversion

- [ ] One primary CTA per screen; its label says what happens ("Bet now", "Download for Android").
- [ ] Primary CTA visible without scrolling at 375×667.
- [ ] No navigation on one-page landing and app pages (header variant `minimal`).
- [ ] Trust signals (licence, rating, payout time) sit next to the CTA, not only in the footer.
- [ ] Every outbound brand link uses the project's tracking URL.

## Compliance (per market)

- [ ] Age gate present and showing the market age (KZ 21+, AU 18+, BR 18+).
- [ ] Compliance panel present in the footer; copy matches `markets/<id>.json`.
- [ ] Compliance copy approved by the compliance team (remove `_verify` once approved).
- [ ] No welcome-bonus or promo-code offers unless compliance approved `allow_offer`.
- [ ] No casino content on KZ or AU sites.
- [ ] Payment methods listed match the market (no credit cards in AU or BR).

## Offers (promo pages)

- [ ] Every offer on the page matches the brand's live offer today (value, code, terms).
- [ ] `verified_at` in the feed is less than 14 days old; re-build at least weekly.
- [ ] "Exclusive" appears only for codes that really are unique to this site.
- [ ] The tracked link applies the code (or the steps say where to enter it).

## Performance (Core Web Vitals)

- [ ] Lighthouse mobile performance score 90 or higher.
- [ ] LCP under 2.5 s, CLS under 0.1, INP under 200 ms.
- [ ] Only the fonts and weights for the site's preset are loaded (the builder does this).
- [ ] Images in AVIF or WebP with explicit width and height.

## Accessibility

- [ ] Text contrast AA (`php tokens/build.php` fails on any preset below 4.5:1).
- [ ] Visible keyboard focus everywhere, plus a skip link.
- [ ] `<html lang>` matches the locale (`ru-KZ`, `kk-KZ`, `en-AU`, `pt-BR`).
- [ ] Every interactive element is at least 44×44 px on mobile.

## Localisation

- [ ] Kazakh letters render in the display and body fonts (Әә Ғғ Ққ Ңң Өө Ұұ Үү Һһ Іі).
- [ ] Longest locale string fits buttons and headings at 360 px.
- [ ] Money and number formats follow the market (`12 500 ₸`, `A$1,250`, `R$ 1.250`).

## Footprint (network diversity)

- [ ] `"prefix": "auto"` with a unique seed; no `sx-` classes in the output (`grep -c "sx-" index.html` returns 0).
- [ ] Preset, header, hero and footer variants differ from the project's other sites of the same type.
- [ ] Code dialects differ from sibling sites: `php build/diversity.php` prints no "same class style" hint
      (pin `"dialect": {"classes": …}` where it does); `python build/variation-diff.py` shows about 80–95% code difference between sites.
- [ ] Different hosting, analytics IDs and asset filenames from sibling sites (outside this system's scope; tracked per project).

## SEO basics

- [ ] Unique `<title>` and meta description; one `<h1>`.
- [ ] schema.org markup for the site type (added per type from Phase 1).
- [ ] No content hidden behind the age gate for crawlers; the gate is script-driven.
