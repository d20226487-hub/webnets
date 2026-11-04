# Brief 05: Multi-page brand satellite (home + service pages)

Status: **Demo A built** · Phase 5 · Site type id: `satellite` · Scope: multi-page plumbing + Demo A only · Guide: [docs/types/satellite.md](../types/satellite.md)

Phase 6 (multi-page app download) is skipped. Demos B and C of this type are deferred. The main output of this
phase is the **multi-page plumbing** that Phase 8 (multi-brand review site) and Phase 9 (news site) will reuse.

## 1. Job of the site

A small site about one brand: a home page plus 3–5 service pages that each answer one search intent
("vantro sign up", "vantro withdrawal", "vantro app"). Every page converts on its own; the site as a whole earns
rankings for brand + modifier searches that a one-page landing cannot cover.

| | |
|---|---|
| Primary conversion | Brand CTA click on any page (one tracked link, per-page position tags) |
| Primary KPI | Site-wide outbound CTR; per-page CTR for service pages |
| Secondary KPIs | Entry pages (which service pages bring traffic), internal click-through from home to service pages |
| Traffic | Brand + modifier searches, landing on the matching service page rather than the home page |
| Visitor mindset | One specific question ("how do I withdraw?"), answered fast, then "OK, let me sign up" |

## 2. Page set

Pages come from the blueprint, like sections do. Each page has its own section list, angle-dependent options and
market rules.

| Page id | Slug | Rule | Answers | Main sections |
|---|---|---|---|---|
| home | `/` | Required | "Is this brand good, where do I start?" | hero, trust, features, page-links (cards to service pages), faq, final |
| signup | `/sign-up/` | Required | "How do I open an account, what do I need?" | page-hero, steps, requirements, prose, faq, final |
| payments | `/payments/` | Required | "How do I deposit and withdraw, how fast?" | page-hero, payments table, prose, faq, final |
| app | `/app/` | When the brand has an app in the market | "Is there an app, how do I get it?" | page-hero, install buttons (reuses Phase 2 parts), features, faq, final |
| sports or casino | `/betting/` or `/casino/` | Per brand vertical allowed in the market | "What can I bet on / play?" | page-hero, showcase, features, faq, final |
| bonus | `/bonus/` | **Only when the market's promotion policy allows a type** (never in KZ/AU/BR today) | Current offer | reuses Phase 3 offer blocks |
| responsible | `/responsible-gambling/` | Required in AU (and wherever compliance says) | Limits, self-exclusion, help lines | prose, help-links |

Every page carries the header with navigation, the compliance strip and footer, the age gate (once per visit, not per
page) and the sticky CTA on phones.

## 3. The plumbing (what this phase really builds)

| Piece | What it does | Reused by |
|---|---|---|
| **Page blueprints** | `pages` in the blueprint: per page id, slug, nav label, rule, sections, order rules. One seed drives page set, section order and variants across the whole site | 8, 9 |
| **Links and URLs** | `sx_page_url('payments')` builds every internal link. Two URL styles: `pretty` (`/payments/`, folder + `index.html`) for hosting, `files` (`payments.html`, relative) for review links and plain file hosting. Optional `base` for sites in a subfolder | 8, 9 |
| **Navigation** | Header with nav (desktop) + no-JavaScript menu on phones (`<details>`), `aria-current` on the active page; footer nav column. Labels from the blueprint, overridable per site | 8, 9 |
| **Breadcrumbs** | On every inner page, with `BreadcrumbList` JSON-LD | 8, 9 |
| **Inner-page hero** | Short `page-hero` (title, one line, CTA) so service pages reach their answer quickly | 8, 9 |
| **Prose block** | The SEO text of a service page: headings, paragraphs, lists, a simple table, optional table of contents. Content comes from the content pipeline, the block only formats it | 7, 8, 9 |
| **Page links** | Cards from home to service pages and "related pages" at the end of each service page | 8, 9 |
| **Per-page SEO** | Title, description and canonical per page (`site.url` + slug), unique title/description check, `WebSite` + `WebPage` JSON-LD | 8, 9 |
| **Sitemap and robots** | `sitemap.xml` and `robots.txt` written by the builder | 8, 9 |
| **Site checks** | Build fails on a broken internal link or a page no menu links to; warns on duplicate titles/descriptions, a page without a CTA, a page over 60 KB | 8, 9 |
| **Multi-page review export** | All pages published as one review link where the navigation works | all |
| **WordPress** | Each page is a WP page using the existing page template with a `page` id; navigation from the site config (WP menus as an option later) | 8, 9 |

CSS stays inlined per page (each page ships only what it uses), so the 60 KB budget is per page, as today.

## 4. Conversion rules (in addition to the shared ones)

1. **Each service page answers its question in the first screen** on phones: page-hero title + one-line answer
   ("Withdrawals to PayID arrive in about 5 minutes") + CTA.
2. **One CTA label site-wide**, position tags per page (`data-cta="hero"` plus `data-page="payments"`) so analytics
   can tell pages apart.
3. **Navigation never competes with the CTA**: at most 5 nav items; the CTA stays the only filled button in the header.
4. Home links to every service page above the FAQ; every service page ends with related pages + final CTA.

### Anti-patterns (never)

- Thin pages: a service page with no real answer, just a CTA (the build warns when the prose block is empty)
- The same text on several pages (content pipeline responsibility; the build warns on duplicate titles/descriptions)
- A bonus page in a market whose policy does not allow promotions
- Mega-menus, carousels, pop-ups beyond the required age gate

## 5. Angles (site-level)

| Angle | Home leads with | Pages on | Notes |
|---|---|---|---|
| `starter-guide` | "Start betting with {brand} in 3 steps" | home, signup, payments, app, responsible | Demo A |
| `fast-payouts` | Payout speed and local payments | home, payments, signup, app | |
| `sports-depth` | Markets, live, same-game multis | home, betting, signup, payments | Sports brands only |

## 6. Demo A

| Brand | Market | Language | Preset | Angle | Pages |
|---|---|---|---|---|---|
| Vantro | AU | EN | clean-sportsbook (not used by any Vantro demo yet) | starter-guide | home, sign-up, payments, app, responsible-gambling |

AU tests the hard parts: compliance strip on every page, mandated tagline, no casino page, no bonus page, a required
responsible-gambling page.

## 7. Acceptance criteria for Demo A

- [x] 5 pages, each under 60 KB, no warnings; `sitemap.xml` and `robots.txt` written
- [x] Every page reachable from the header and footer nav; no broken internal links; active page marked
- [x] Phone menu works without JavaScript; CTA above the fold on every page at 375×667
- [x] Breadcrumbs + JSON-LD on inner pages; unique title, description and canonical per page
- [x] Same build in `pretty` and `files` URL styles; the review link lets you click through all pages
- [x] A bonus page cannot be switched on in AU; a casino page cannot be switched on in AU

## 8. Open questions

1. **URL style on your hosting:** pretty folders (`/payments/`) or files (`payments.html`)? Both will work; which is the default?
2. **WordPress navigation:** is navigation from the site config enough, or must it be an editable WP menu from day one?
3. **Page set:** are home, sign-up, payments, app, responsible gambling and betting/casino the right service pages, or
   do your current satellites use others (login, verification, support, odds)?
4. **Languages:** multi-page sites in KZ will need RU and KK versions of every page with `hreflang`. Build that now, or
   when a KZ satellite is needed?
