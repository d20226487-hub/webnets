# Site type: gambling news site with PR articles (`news`)

Status: **Phase 9: Demos A, B, C (sample articles).** Brief: [docs/briefs/09-gambling-news-pr.md](../briefs/09-gambling-news-pr.md) ·
Blueprint: [blueprints/news.json](../../blueprints/news.json) · Schemas: `dist/schemas/news.schema.json` (site config),
[docs/schemas/article.schema.json](../schemas/article.schema.json) (one article)

| Demo | Config | Review link (all pages clickable) |
|---|---|---|
| A · Paddock Wire · AU · EN · 12 sample articles (4 news, 4 guides, 4 PR) · 17 pages | [sites/demos/news-a-au.json](../../sites/demos/news-a-au.json) | https://claude.ai/artifact/QkSvnYTzwgyJDB8CVXG1eF |
| B · Placar Aberto · BR · PT · guides-first · stack lead · 8 sample articles (2 PR) · 13 pages | [sites/demos/news-b-br.json](../../sites/demos/news-b-br.json) | https://claude.ai/artifact/Y9zghU3Pnb8A1SuCJwKCU5 |
| C · Ставки Дня · KZ · RU · brand-watch · numbered wire lead · 8 sample articles (2 PR) · 13 pages | [sites/demos/news-c-kz.json](../../sites/demos/news-c-kz.json) | https://claude.ai/artifact/5jCmT1ttiCN1bCL1F4vpej |

## 1. What the site is for

A small betting-industry news site whose business is **paid PR articles** about brands. News and guides earn trust
and traffic; PR articles carry the brand links. Everything paid is labelled, every time.

## 2. Content

| Data | Lives in |
|---|---|
| Articles, one JSON file each | `content/<site>/articles/<slug>.json` (schema: `docs/schemas/article.schema.json`) |
| Authors | `content/<site>/authors.json` |
| Publisher identity | `brands/publishers.json` |
| Brand links for PR | site config `links.brands.<id>` |

Article fields: `slug`, `kind` (`news` / `guide` / `pr`), `title`, `dek`, `date`, `updated`, `author`, `sponsor` (pr),
`brand` (pr, optional), `body` (HTML, same sanitizer as `prose`), `sample` (made-up demo content).

- **Scheduled publishing:** articles dated after the build's `today` are left out until that date. Rebuild daily.
- **Sample articles** need `"demo": true` (the build refuses them otherwise). A visible sample banner is opt-in with `"sample_notice": true`; the demo gallery leaves it off.

## 3. Pages

| Page | Slug | Sections |
|---|---|---|
| home | `/` | lead stories (H1 masthead; `split`: newest large with two beside it, `stack`: newest full width with three beneath, `list`: numbered wire of six; never PR; lists below skip the stories the lead shows), latest, partner-content rail (labelled), guides |
| article (generated, one per article) | `/news/{slug}/`, `/guides/{slug}/`, `/partner-content/{slug}/` | breadcrumbs (Home / section), kicker or Sponsored label + sponsor, H1, dek, byline, dates, reading time, disclosure box (PR), body, brand box with CTA (PR with a brand), read next |
| news, guides, partner content | `/news/`, `/guides/`, `/partner-content/` | article list with H1, paged at 10 |
| about | `/about/` | what sponsored means, what sponsors cannot do, corrections, help |

**CTAs:** only paid articles have them (brand box + sticky bar naming the brand). The header has no CTA, and the
sticky bar hides itself on pages without a brand. The "page has no CTA" check is off for this type (`cta_check: false`).

**Sharing and authors.** Every article has share links under the byline and after the body (copy link, X, Facebook,
Telegram, WhatsApp; order follows the market: Telegram first in KZ, WhatsApp first in BR). They are plain links with no
third-party scripts or pixels. After the body, an author box shows initials, name, role, bio (`authors.json`: `name`,
`role`, `bio`) and how many articles the author has on the site; PR pieces show the partner desk as "Prepared by".
The author's role and bio also go into the NewsArticle JSON-LD.

**Image mode.** `"images": true` adds pictures: thumbnails on article and link cards, the lead story's picture, a hero
picture on single articles and on text pages, and reviewer avatars on review cards. An item's own `image` (URL, with
`image_alt`) is used when present; otherwise a generated cover (inline SVG in the site's colour tokens, seeded layout,
a pictogram picked from the title: football, hockey, payments, phone, licence, odds, limits, casino, chat, star) stands
in. The review link of the Russian demo contains both modes (`img-*.html`), with a bar on top that switches the page.

## 4. Guardrails the code enforces

| Guardrail | Result |
|---|---|
| Sample articles without `"demo": true` | **Build fails** |
| PR article without a sponsor | **Build fails** |
| Non-PR article with a sponsor or brand (unlabelled paid content) | **Build fails** |
| PR for a brand not licensed in the market, or whose verticals the market forbids (e.g. casino in AU) | **Build fails** |
| PR brand without a tracked link, or off the market's domain | **Build fails** |
| Fewer than 3 published articles; missing required fields | **Build fails** |
| Offer words (bonus, free bet, promo code, odds boost, cashback, local equivalents) in PR where the market restricts inducements | Warning per word |
| Unknown author | Warning |
| Sponsored label + sponsor on every PR card, rail heading and article; disclosure box on PR articles | Blocks |
| PR brand links `rel="nofollow sponsored"` | `sx_brand_cta()` |
| `NewsArticle` / `Article` structured data on news and guides only; none on PR | `article-body` |
| PR never the lead story | `article-lead` |

## 5. Build a news site

1. Create `content/<site>/articles/` and `authors.json`; write articles to the schema.
2. Copy [sites/templates/news.json](../../sites/templates/news.json); set publisher, market, `articles`, brand links, `site.url`.
3. `php build/build.php sites/<project>/<id>.json`; review link: `php build/export-review-site.php sites/<project>/<id>.json "Title"`.
4. Rebuild on every new article and daily (scheduled articles go live by date).

## 6. Plumbing added

- Generated pages from an **article store** (`"generate": "articles"`, `{section}/{item}` slugs, `parents` for breadcrumbs).
- Shared HTML sanitizer `sx_safe_html()` (prose and article bodies).
- `sample_notice` banner for any made-up content type (reviews, articles); its CSS ships only when used.
- Header `show_cta`, self-hiding sticky CTA, configurable `disclosure` wording and link.

## 7. Known gaps

- Google News sitemap and publisher pages (brief question 3).
- Images: text-first with CSS covers; no licensed photos yet (question 4).
- Editorial workflow for PR approval lives outside the builder (question 1).
- Demos B and C.

**Variation demo (Russian, KZ):** `php build/export-review-site.php sites/demos/news-c-kz.json` builds topic (general news / brand news = the Nyxa-only article set in `content/stavki-dnya-kz-nyxa`, with its own titles and meta) × images (off / on) × lead (list / split / stack) × partner content (block = separate labelled section / feed = PR items mixed into the latest feed, still labelled as advertising). Review link: https://claude.ai/artifact/5jCmT1ttiCN1bCL1F4vpej.
