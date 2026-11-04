# Brief 09: Gambling news site with PR articles

Status: **Demo A built** (without a separate approval step, at the user's request) · Guide: [docs/types/news.md](../types/news.md) · Phase 9 · Site type id: `news` · Builds on the
Phase 5 multi-page plumbing, Phase 7 sample-content guard and Phase 8 generated pages and publisher identity

## 1. Job of the site

A small news site about the betting industry in one market. Most articles are **paid PR articles** about brands
(launches, features, partnerships). Editorial news and how-to guides around them give the site a reason to exist and
to rank. PR articles link to the brand with a tracked link.

| | |
|---|---|
| Primary conversion | Clicks from PR articles to the brand (tracked, `rel="sponsored"`) |
| Business model | Brands pay for PR placements; the site sells reach and links |
| Primary KPI | PR article reads and outbound clicks per article |
| Secondary KPIs | Organic entries on news and guides, pages per visit, partner-section visits |
| Traffic | News and how-to searches ("credit card ban betting", "how betstop works"), brand news searches, links |
| Visitor mindset | Reading, not shopping. They tolerate sponsored content only if it is clearly labelled |

## 2. Labelling paid content (the core rule)

PR articles are advertising. All three markets require advertising to be identifiable, and search engines require
paid links to be marked. So:

| Rule | Enforced by |
|---|---|
| Every article has `kind`: `news`, `guide` or `pr` | Article store |
| `pr` articles need a `sponsor`; they show a **Sponsored** label (localized: Publicidade, Реклама, Жарнама) on every card and above the headline, plus a disclosure box | `article-*` blocks, build fails without a sponsor |
| PR links to the brand carry `rel="sponsored nofollow"` | `sx_brand_cta()` |
| PR articles live in their own section (`/partner-content/`), never as the home lead story | Blueprint and `article-lead` |
| PR articles get no `NewsArticle` structured data (paid content must not look like news to search engines) | Builder |
| A site-wide line says some articles are paid for and labelled | `disclosure` block |

## 3. Gambling-advertising rules for PR

PR articles are also gambling advertising, so market rules apply to their text:

- The brand must be licensed in the market and its vertical allowed there (no casino PR in AU or KZ): **build fails** otherwise.
- Where the market restricts inducements (AU, KZ, BR today), PR text is checked for offer words (bonus, free bet,
  promo code, odds boost, cashback and local equivalents): **warning** per match, to be fixed before publishing.
- Compliance strip with mandated taglines (AU) on every page, age line, responsible-gambling links in the footer.

## 4. Content and data

| Data | Lives in | Who maintains it |
|---|---|---|
| Articles: slug, kind, category, title, dek, date, updated, author, sponsor, brand, body HTML, `sample` | `content/<site>/articles/*.json` (one file per article) | Editors and the PR team (or a content pipeline, schema in `docs/schemas/article.schema.json`) |
| Authors | `content/<site>/authors.json` | Editors |
| Publisher identity | `brands/publishers.json` (Phase 8) | |
| Brand tracked links | site config `links.brands.<id>` | Per site |

- Articles dated after `today` are **scheduled**: left out of the build, not an error.
- Sample (made-up) articles need `"demo": true` and get a sample banner on every page, like Phase 7 reviews.
- Body HTML goes through the same sanitizer as `prose` (basic tags, `page:` links, `https://` links).

## 5. Pages

| Page | Slug | Sections |
|---|---|---|
| home | `/` | lead story (latest news or guide, never PR), latest list, partner-content rail (labelled), guides list |
| article (one per article) | `/news/{slug}/`, `/guides/{slug}/`, `/partner-content/{slug}/` | breadcrumbs, kicker or Sponsored label, H1, dek, byline and dates, disclosure box (PR), body, brand box with CTA (PR with a brand), related articles |
| news, guides | `/news/`, `/guides/` | list with paging (reuses Phase 7 paging) |
| partner content | `/partner-content/` | labelled list of PR articles with an explanation |
| about | `/about/` | editorial policy: what sponsored means, corrections, contact |

New plumbing: **generated pages from an article store**, scheduled publishing, a shared HTML sanitizer, a header
without a CTA, and a sticky CTA that hides itself when the page has no brand to link to.

## 6. Conversion

- The brand box closes every PR article: logo, one line, "Visit {brand}" button, age line. A sticky bar names the brand
  on phones.
- News and guides carry no brand CTA (they earn trust and traffic); they link to related PR content through the rail.
- Lists and cards are text-first (no stock photos or unlicensed images; CSS covers in the category colour).

### Anti-patterns (never)

- Unlabelled or vaguely labelled PR ("In partnership with" in small grey text)
- PR as the lead story, or PR mixed into "Latest news" without its label
- Offers, bonus codes or odds boosts in PR where the market restricts them
- Fake news about real companies or regulators; sample articles about real entities must state only verifiable facts

## 7. Demo A

| Market | Language | Publisher | Preset | Articles |
|---|---|---|---|---|
| AU | EN | Paddock Wire (fictional) | editorial | 12 sample articles: 4 news, 4 guides, 4 PR (Vantro, Nyxa) |

## 8. Acceptance criteria for Demo A

- [x] PR articles labelled on cards and pages, with disclosure box, `rel="sponsored"`, no NewsArticle markup
- [x] Build fails for PR without a sponsor, for casino PR in AU, and for sample articles without `"demo": true`
- [x] Offer words in AU PR give a warning; a scheduled (future-dated) article is left out
- [x] Home lead story is never PR; partner rail is labelled; header has no CTA; sticky CTA only on PR pages
- [x] Article H1 and dek above the fold at 375×667; under 60 KB per page; no warnings

## 9. Open questions (answer any time; the demo uses the defaults in brackets)

1. Who writes PR articles, and does an editor approve them before publishing? [editor approves]
2. Do sponsors get dofollow links? [no: `rel="sponsored nofollow"`, as search engines require]
3. Should news articles be submitted to Google News (needs a news sitemap and an about/contact page)? [later]
4. Real images: licensed photos per article, or text-first design? [text-first]
5. Demos B and C? [later]
