"""Demo gallery page: dist/gallery/gallery.html from sites/demos/*.json, the review links in docs/types/*.md and
   the screenshots made by build/screenshots.ps1. English and Russian interface, light/dark/auto theme.
   Usage: python build/gallery.py [--local] [satellite-system folder]
   --local writes dist/index.html instead, linking every demo to its local review pages (dist/review/<id>/index.html)
   so the whole set can be viewed without publishing: run build/serve-local (or php -S localhost:8080 -t dist)."""
import sys, os, json, glob, re, html
LOCAL = '--local' in sys.argv
args = [a for a in sys.argv[1:] if a != '--local']
root = args[0] if args else os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
J = lambda p: json.load(open(os.path.join(root, p), encoding='utf-8'))

ops = J('brands/operators.json'); pubs = J('brands/publishers.json')
LANG = {'en': ('English', 'Английский'), 'ru': ('Russian', 'Русский'), 'pt': ('Portuguese', 'Португальский'), 'kk': ('Kazakh', 'Казахский')}
MARKET = {'au': ('Australia', 'Австралия'), 'kz': ('Kazakhstan', 'Казахстан'), 'br': ('Brazil', 'Бразилия'), 'xx': ('Demo market', 'Демо-рынок')}
MARKET_DEFAULT_LOCALE = {'au': 'en', 'kz': 'ru', 'br': 'pt', 'xx': 'en'}
ANGLE_RU = {
    'matchday': 'День матча', 'fast-payout': 'Быстрые выплаты', 'fast-payouts': 'Быстрые выплаты', 'app-first': 'Сначала приложение',
    'trusted': 'Надёжность', 'live-casino': 'Живое казино', 'official-app': 'Официальное приложение', 'live-in-pocket': 'Лайв в кармане',
    'apk-easy': 'Простой APK', 'fast-app': 'Быстрое приложение', 'welcome': 'Приветственный бонус', 'code-first': 'Сначала код',
    'everyday': 'Каждый день', 'loyalty': 'Лояльность', 'new-releases': 'Новинки', 'popular': 'Популярное', 'studio': 'Студия',
    'starter-guide': 'Гайд для новичка', 'sports-depth': 'Глубина линии', 'payout-focus': 'Акцент на выплаты', 'balanced': 'Сбалансированный',
    'support-focus': 'Акцент на поддержку', 'overall': 'Общий рейтинг', 'newsroom': 'Редакция', 'guides-first': 'Сначала гайды',
}

def ru_plural(n, one, few, many):
    if n % 10 == 1 and n % 100 != 11: return one
    if 2 <= n % 10 <= 4 and not 12 <= n % 100 <= 14: return few
    return many

# review links from the type guides
SHOTS = 'gallery/shots/' if LOCAL else 'shots/'
links = {}
for f in glob.glob(os.path.join(root, 'docs/types/*.md')):
    for m in re.finditer(r'sites/demos/([a-z0-9-]+)\.json\)[^\n]*?(https://claude\.ai/artifact/[A-Za-z0-9]+)', open(f, encoding='utf-8').read()):
        links[m.group(1)] = m.group(2)
DOC_IDS = {m.group(1) for f in glob.glob(os.path.join(root, 'docs/types/*.md')) for m in re.finditer(r'sites/demos/([a-z0-9-]+)\.json\)', open(f, encoding='utf-8').read())}

# (id, phase, (name en, ru), (description en, ru), status key)
TYPES = [
  ('landing', 1, ('One-page branded landing', 'Брендовый лендинг'), ('Sells the brand on one page and sends visitors to sign up.', 'Продаёт бренд на одной странице и ведёт на регистрацию.'), 'ready'),
  ('app', 2, ('One-page app download', 'Страница приложения (лендинг)'), ('Looks and works like a store listing: install buttons, QR code, APK guide.', 'Выглядит и работает как страница в магазине приложений: кнопки установки, QR-код, инструкция для APK.'), 'ready'),
  ('promo', 3, ('One-page bonuses and promo codes', 'Бонусы и промокоды (лендинг)'), ('Current offers with codes and the terms in plain words.', 'Актуальные предложения с кодами и условиями простыми словами.'), 'demo_market'),
  ('vendor', 4, ('One-page vendor overview', 'Обзор провайдера (лендинг)'), ('One game studio as a brand carries it: new and popular games, honest RTP.', 'Одна игровая студия у конкретного бренда: новые и популярные игры, честный RTP.'), 'ready'),
  ('satellite', 5, ('Multi-page brand satellite', 'Многостраничный сателлит бренда'), ('Home plus service pages for sign-up, payments, app and safer gambling.', 'Главная и сервисные страницы: регистрация, платежи, приложение, ответственная игра.'), 'ready'),
  ('app-multi', 6, ('Multi-page app download', 'Многостраничный сайт приложения'), ('The app page as home, plus iPhone, Android, features, "what is new" and help pages. Same store looks as the one-page app.', 'Страница приложения как главная, плюс разделы iPhone, Android, возможности, что нового и помощь. Те же стили магазинов, что у одностраничной версии.'), 'ready'),
  ('reviews', 7, ('Brand review site', 'Сайт отзывов о бренде'), ('Player reviews of one brand, with computed scores and topic filters.', 'Отзывы игроков об одном бренде с рассчитанными оценками и фильтрами по темам.'), 'ready'),
  ('casinos', 8, ('Multi-brand casino reviews', 'Обзоры нескольких казино'), ('Ranked, scored reviews of licensed casinos, with a comparison table.', 'Рейтинг и обзоры лицензированных казино с таблицей сравнения.'), 'ready'),
  ('news', 9, ('Gambling news with PR', 'Новости гемблинга и PR-статьи'), ('Industry news and guides, with paid articles labelled Sponsored.', 'Новости отрасли и гайды; платные статьи помечены как реклама.'), 'ready'),
  ('casinos-one', 10, ('One-page multi-brand casino reviews', 'Обзоры нескольких казино (лендинг)'), ('The ranking, comparison and how we rate on one page, with every design of the ranking.', 'Рейтинг, сравнение и методика на одной странице, со всеми видами рейтинга.'), 'ready'),
]
STATUS = {'ready': ('Ready', 'Готово', 'ok'), 'demo_a': ('Demo A only', 'Только демо A', 'part'),
          'demo_market': ('Demo market only', 'Только демо-рынок', 'part'), 'none': ('Not built', 'Не сделано', 'none')}

# the date of the newest screenshot: when the gallery's builds were last taken
import datetime
_shots = glob.glob(os.path.join(root, 'dist/gallery/shots', '*-d.png'))
_built = datetime.date.fromtimestamp(max(os.path.getmtime(f) for f in _shots)) if _shots else datetime.date.today()
BUILT_EN = f"{_built.day} {_built.strftime('%b')} {_built.year}"
BUILT_RU = f"{_built.day} " + ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'][_built.month - 1] + f" {_built.year} года"

def esc(s): return html.escape(str(s), quote=True)
def t(en, ru):
    """Both languages in the page; CSS shows the one chosen by the language switch."""
    if en == ru: return esc(en)
    return f'<span data-l="en">{esc(en)}</span><span data-l="ru">{esc(ru)}</span>'

# local gallery only: link to the AI brief settings page (build/settings.php via build/local-router.php)
SETTINGS_LINK = f'<p class="lede"><a href="/settings/" style="font-weight:600">{t("AI brief settings", "Настройки брифа для ИИ")} →</a></p>' if LOCAL else ''

demos = []
skipped = []
for f in sorted(glob.glob(os.path.join(root, 'sites/demos/*.json'))):
    c = json.load(open(f, encoding='utf-8'))
    did = c['id']
    # Only listed demos with screenshots (build/screenshots.ps1 or .sh): published ones have a review link in
    # docs/types/*.md; the local gallery needs only the local review pages (dist/review/<id>/).
    listed = did in links or (LOCAL and os.path.isfile(os.path.join(root, 'dist/review', did, 'index.html')) and did in DOC_IDS)
    if not listed or not os.path.isfile(os.path.join(root, 'dist/gallery/shots', did + '-d.png')):
        skipped.append(did)
        continue
    who = pubs[c['publisher']]['name'] if c.get('publisher') else ops[c['brand']]['name']
    letter = re.search(r'(?:^|-)([abc])-', did + '-')
    letter = letter.group(1).upper() if letter else 'A'
    files = glob.glob(os.path.join(root, 'dist/sites', did, '**/*.html'), recursive=True)
    sizes = [os.path.getsize(p) for p in files]
    loc = c.get('locale') or MARKET_DEFAULT_LOCALE[c['market']]
    extra = []
    if c.get('provider'): extra.append((J('brands/providers.json')[c['provider']]['name'],) * 2)
    if c.get('brands'):
        n = len(c['brands']); extra.append((f'{n} brands', f'{n} ' + ru_plural(n, 'бренд', 'бренда', 'брендов')))
    if c['type'] == 'news':
        n = len(glob.glob(os.path.join(root, c['articles'], '*.json'))); extra.append((f'{n} articles', f'{n} ' + ru_plural(n, 'статья', 'статьи', 'статей')))
    demos.append({
        'id': did, 'type': c['type'], 'letter': letter, 'loc': loc, 'who': who, 'market': c['market'], 'lang': LANG[loc],
        'preset': c['preset'], 'angle': c.get('angle', ''), 'pages': len(files), 'kb': round(max(sizes) / 1024, 1),
        'link': f'review/{did}/index.html' if LOCAL and os.path.isfile(os.path.join(root, 'dist/review', did, 'index.html')) else (None if LOCAL else links.get(did)), 'extra': extra, 'sample': bool(c.get('demo')),
        'title': (c.get('page') or c.get('meta', {}).get('home', {})).get('title', ''),
    })

sections = []
for tid, phase, name, desc, status in TYPES:
    items = sorted([d for d in demos if d['type'] == tid], key=lambda d: ({'ru': 0, 'en': 1, 'pt': 2}.get(d['loc'], 3), d['letter']))  # RU first (it carries the switchers), then EN, then PT
    cards = []
    for d in items:
        angle = (d['angle'].replace('-', ' ').capitalize(), ANGLE_RU.get(d['angle'], d['angle'])) if d['angle'] else None
        meta = [MARKET[d['market']], d['lang'], (d['preset'].replace('-', ' ').title(),) * 2] + ([angle] if angle else []) + d['extra']
        n = d['pages']
        pages = t(f'{n} page' + ('' if n == 1 else 's'), f'{n} ' + ru_plural(n, 'страница', 'страницы', 'страниц'))
        size = t(f'max {d["kb"]} KB', f'макс. {str(d["kb"]).replace(".", ",")} КБ')
        multi = n > 1
        link = (f'<a class="open" href="{esc(d["link"])}" target="_blank" rel="noopener">{t("Open site" if multi else "Open page", "Открыть сайт" if multi else "Открыть страницу")}<span aria-hidden="true">↗</span></a>'
                if d['link'] else f'<span class="open off">{t("Not published", "Не опубликовано")}</span>')
        cards.append(f'''
      <article class="card" data-type="{tid}" data-market="{d['market']}">
        <div class="shots">
          <img class="desk" src="{SHOTS}{d['id']}-d.png" data-alt-en="Desktop view of {esc(d['who'])} demo {d['letter']}" data-alt-ru="Вид на компьютере: {esc(d['who'])}, демо {d['letter']}" alt="Desktop view of {esc(d['who'])} demo {d['letter']}" width="640" height="400" loading="lazy">
          <div class="phone"><img src="{SHOTS}{d['id']}-m.png" data-alt-en="Phone view" data-alt-ru="Вид на телефоне" alt="Phone view" width="416" height="624" loading="lazy"></div>
        </div>
        <div class="body">
          <p class="eyebrow"><span class="letter">{d['letter']}</span>{esc(d['who'])}{' <span class="sample">' + t("Sample content", "Пример контента") + '</span>' if d['sample'] else ''}</p>
          <h3>{esc(d['title'])}</h3>
          <ul class="meta">{''.join(f'<li>{t(*m)}</li>' for m in meta)}</ul>
          <div class="foot"><span class="stat">{pages} · {size}</span>{link}</div>
        </div>
      </article>''')
    if not items:
        cards.append(f'''
      <article class="card empty" data-type="{tid}" data-market="none">
        <div class="shots blank"><p>{t("No demo yet", "Демо пока нет")}</p></div>
        <div class="body"><p class="eyebrow">{t(f"Phase {phase}", f"Этап {phase}")}</p><h3>{t("Skipped for now", "Пока пропущено")}</h3>
          <p class="note">{t("The app blocks from Phase 2 and the multi-page plumbing from Phase 5 already exist, so this type mostly needs its page set and APK rules.",
                             "Блоки приложения из этапа 2 и многостраничная основа из этапа 5 уже есть, поэтому для этого типа нужны в основном набор страниц и правила для APK.")}</p></div>
      </article>''')
    s_en, s_ru, s_cls = STATUS[status]
    sections.append(f'''
    <section class="type" id="{tid}" data-type="{tid}">
      <header class="type-head">
        <span class="phase">{phase:02d}</span>
        <div><h2>{t(*name)}</h2><p>{t(*desc)}</p></div>
        <span class="status {s_cls}">{t(s_en, s_ru)}</span>
      </header>
      <div class="grid">{''.join(cards)}
      </div>
    </section>''')

# "By section": every block design pictured on its own (build/sections.py), grouped by what the section does
SEC_GROUPS = [
    ('first', ('First screens', 'Первые экраны'), ['hero', 'app-hero', 'offer-hero', 'vendor-hero', 'toplist', 'page-hero', 'article-lead', 'brand-header', 'store-header', 'rating-summary']),
    ('conversion', ('Conversion', 'Конверсия'), ['cta-band', 'promo-list', 'promo-terms', 'download-panel', 'bonus-card']),
    ('trust', ('Trust and scoring', 'Доверие и оценки'), ['trust-bar', 'criteria', 'compare-table', 'score-box', 'pros-cons', 'facts-table', 'topic-scores', 'editor-box']),
    ('product', ('Product', 'Продукт'), ['features', 'showcase', 'game-grid', 'rtp-explainer', 'provider-links', 'screenshots', 'app-info', 'changelog', 'install-guide', 'payments', 'store-about', 'store-reviews', 'app-privacy']),
    ('guidance', ('Guidance', 'Помощь'), ['steps', 'faq', 'prose']),
    ('lists', ('Lists and navigation', 'Списки и навигация'), ['page-links', 'review-list', 'review-form']),
    ('articles', ('Articles', 'Статьи'), ['article-body', 'article-list']),
]
SEC_RU = {'hero': 'Первый экран', 'app-hero': 'Первый экран приложения', 'offer-hero': 'Первый экран с бонусом', 'vendor-hero': 'Первый экран провайдера',
    'toplist': 'Рейтинг брендов', 'page-hero': 'Заголовок страницы', 'article-lead': 'Главная новость', 'brand-header': 'Шапка обзора бренда',
    'store-header': 'Карточка в стиле магазина', 'rating-summary': 'Сводка оценок', 'cta-band': 'Финальный призыв', 'promo-list': 'Список акций',
    'promo-terms': 'Условия акции', 'download-panel': 'Панель загрузки', 'bonus-card': 'Карточка бонуса', 'trust-bar': 'Полоса доверия',
    'criteria': 'Критерии оценки', 'compare-table': 'Таблица сравнения', 'score-box': 'Разбор оценки', 'pros-cons': 'Плюсы и минусы',
    'facts-table': 'Факты о бренде', 'topic-scores': 'Оценки по темам', 'editor-box': 'Блок редакции', 'features': 'Возможности',
    'showcase': 'Витрина продукта', 'game-grid': 'Сетка игр', 'rtp-explainer': 'Пояснение RTP', 'provider-links': 'Другие провайдеры',
    'screenshots': 'Скриншоты', 'app-info': 'Информация о приложении', 'changelog': 'Что нового', 'install-guide': 'Инструкция по установке',
    'payments': 'Платежи', 'store-about': 'Описание (магазин)', 'store-reviews': 'Отзывы (магазин)', 'app-privacy': 'Конфиденциальность',
    'steps': 'Как начать', 'faq': 'Вопросы и ответы', 'prose': 'Текст страницы', 'page-links': 'Ссылки на страницы', 'review-list': 'Список отзывов',
    'review-form': 'Форма отзыва', 'article-body': 'Статья', 'article-list': 'Список статей'}
TYPE_NAMES = {x[0]: x[2] for x in TYPES}
used = {}  # block -> {'home': set(types), 'inner': set(types)}
for f in glob.glob(os.path.join(root, 'blueprints/*.json')):
    bp = json.load(open(f, encoding='utf-8'))
    tname = os.path.basename(f)[:-5]
    for pid, pg in (bp['pages'].items() if 'pages' in bp else [('home', bp)]):
        lay = [s_ for k, v in (pg.get('layouts') or bp.get('layouts') or {}).items() if isinstance(v, list) for s_ in v] if pid == 'home' else []
        for s_ in pg.get('sections', []) + lay:
            used.setdefault(s_['block'], {'home': set(), 'inner': set()})['home' if pid == 'home' else 'inner'].add(tname)
sec_file = os.path.join(root, 'dist/gallery/sections.json')
sec_items = json.load(open(sec_file, encoding='utf-8')) if LOCAL and os.path.isfile(sec_file) else []
sec_by = {}
for x in sec_items:
    sec_by.setdefault(x['block'], []).append(x)
sec_groups_html = []
sec_types, sec_blocks = set(), []
n_sec_designs = len(sec_items)
for gid, gname, blocks in SEC_GROUPS:
    cards_ = []
    for bl in blocks:
        if bl not in sec_by:
            continue
        meta = json.load(open(os.path.join(root, 'blocks', bl, 'block.json'), encoding='utf-8'))
        en = re.sub(r'^SX ', '', meta.get('title', bl))
        u = used.get(bl, {'home': set(), 'inner': set()})
        where = [t(f"Home: {', '.join(TYPE_NAMES.get(x, (x, x))[0] for x in sorted(u['home']))}", f"Главные: {', '.join(TYPE_NAMES.get(x, (x, x))[1] for x in sorted(u['home']))}")] if u['home'] else []
        where += [t(f"Inner pages: {', '.join(TYPE_NAMES.get(x, (x, x))[0] for x in sorted(u['inner']))}", f"Внутренние: {', '.join(TYPE_NAMES.get(x, (x, x))[1] for x in sorted(u['inner']))}")] if u['inner'] else []
        designs = sorted(sec_by[bl], key=lambda x: x['design'])
        n = len(meta['sx'].get('variants') or [1])
        types_ = sorted(u['home'] | u['inner'], key=lambda x: [y[0] for y in TYPES].index(x) if x in TYPE_NAMES else 99)
        sec_types.update(types_)
        share = (t(f"Shared by {len(types_)} site types", f"Общая для {len(types_)} " + ru_plural(len(types_), 'типа', 'типов', 'типов') + " сайтов") if len(types_) > 1
                 else t(f"Only {TYPE_NAMES.get(types_[0], (types_[0],))[0]}", f"Только {TYPE_NAMES.get(types_[0], (types_[0], types_[0]))[1]}") if types_ else '')
        sec_blocks.append((gid, bl, en, SEC_RU.get(bl, en)))
        tiles = ''.join(f'''
          <figure class="dtile">
            <a class="shots" href="{esc(x['page'])}" target="_blank" rel="noopener" title="{esc(x['page'])}">
              <img class="desk" src="{esc(x['desktop'])}" alt="{esc(en)}: {esc(x['design'])}" width="640" height="450" loading="lazy">
              <span class="phone"><img src="{esc(x['phone'])}" alt="" width="416" height="624" loading="lazy"></span>
            </a>
            <figcaption><b>{esc(x['design'].replace('-', ' '))}</b><a href="{esc(x['page'])}" target="_blank" rel="noopener">{t("Open page", "Открыть страницу")} ↗</a></figcaption>
          </figure>''' for x in designs)
        cards_.append(f'''
      <article class="scard" data-group="{gid}" data-block="{bl}" data-types=" {' '.join(types_)} " data-share="{'many' if len(types_) > 1 else 'one'}">
        <header class="shead"><h3>{t(en, SEC_RU.get(bl, en))}</h3><span class="sshare{' many' if len(types_) > 1 else ''}">{share}</span><span class="scount">{t(f"{len(designs)} of {n} designs", f"{len(designs)} из {n} " + ru_plural(n, 'дизайна', 'дизайнов', 'дизайнов'))}</span></header>
        <ul class="meta">{''.join(f'<li>{w}</li>' for w in where)}</ul>
        <div class="dgrid">{tiles}
        </div>
      </article>''')
    if cards_:
        sec_groups_html.append(f'''
    <section class="sgroup" id="s-{gid}">
      <header class="type-head"><span class="phase" data-gcount>{len(cards_):02d}</span><div><h2>{t(*gname)}</h2></div></header>
      {''.join(cards_)}
    </section>''')
SEC_VIEW = f'''
  <div id="secview" hidden>
    <div class="filters sfilters" role="group" aria-label="Filter sections / Фильтр секций">
      <div class="frow"><span class="lbl">{t("Site type", "Тип сайта")}</span>{''.join(f'<button type="button" class="chip" data-sfilter="type" data-value="{x[0]}" aria-pressed="false">{t(*x[2])}</button>' for x in TYPES if x[0] in sec_types)}</div>
      <div class="frow"><span class="lbl">{t("Section type", "Тип секции")}</span>{''.join(f'<button type="button" class="chip" data-sfilter="group" data-value="{g[0]}" aria-pressed="false">{t(*g[1])}</button>' for g in SEC_GROUPS if any(b in sec_by for b in g[2]))}</div>
      <div class="frow"><span class="lbl">{t("Section", "Секция")}</span><select id="sblock" class="ssel" aria-label="Section / Секция"><option value="" data-en="All sections" data-ru="Все секции">All sections</option>{''.join(f'<option value="{b}" data-group="{g}" data-en="{esc(en_)}" data-ru="{esc(ru_)}">{esc(en_)}</option>' for g, b, en_, ru_ in sec_blocks)}</select></div>
      <div class="frow"><span class="lbl">{t("Use", "Где")}</span><button type="button" class="chip" data-sfilter="share" data-value="many" aria-pressed="false">{t("Shared by several types", "Общие для нескольких типов")}</button><button type="button" class="chip" data-sfilter="share" data-value="one" aria-pressed="false">{t("One type only", "Только у одного типа")}</button><span class="scount" id="sshown"></span><button type="button" class="clear" id="sclear" hidden>{t("Show all", "Показать все")}</button></div>
    </div>
    <p class="none-msg" id="snone" hidden>{t("No section matches these filters.", "Нет секций под эти фильтры.")}</p>
    {''.join(sec_groups_html)}
  </div>''' if sec_groups_html else ''
VIEW_SWITCH = f'''<div class="seg" role="group" aria-label="View / Вид"><button type="button" data-view="sites" aria-pressed="true">{t("By site", "По сайтам")}</button><button type="button" data-view="sections" aria-pressed="false">{t(f"By section ({n_sec_designs})", f"По секциям ({n_sec_designs})")}</button></div>''' if sec_groups_html else ''

markets = sorted({d['market'] for d in demos}, key=lambda m: list(MARKET).index(m))
chips_type = ''.join(f'<button type="button" class="chip" data-filter="type" data-value="{x[0]}" aria-pressed="false">{t(*x[2])}</button>' for x in TYPES)
chips_market = ''.join(f'<button type="button" class="chip" data-filter="market" data-value="{m}" aria-pressed="false">{t(*MARKET[m])}</button>' for m in markets)
n_pages = sum(d['pages'] for d in demos)
n_types = len(TYPES); n_built = len({d['type'] for d in demos}); n_demos = len(demos); n_langs = len({d['lang'] for d in demos})

DARK = '--bg: #111417; --paper: #1A1E23; --ink: #E8ECEF; --muted: #9AA3AD; --line: #2C333A; --accent: #5CC4B2; --accent-soft: #163631; --warn: #F2C266; --warn-soft: #3A2E12; --off: #7D8690; color-scheme: dark;'
page = f'''<title>WebNets Gallery</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap&subset=cyrillic">
<style>
/* Layout: a contact sheet. One band per site type in build order; cards pair the desktop shot with an inset phone shot.
   Language: both languages are in the page; :root[data-lang] hides the other one. Theme: light by default, dark or auto on request. */
:root {{
  --bg: #EEF0F2; --paper: #FFFFFF; --ink: #15181C; --muted: #5B636D; --line: #D5DAE0; --accent: #1D6B5F; --accent-soft: #DCEDEA;
  --warn: #8A5A00; --warn-soft: #FDF0D2; --off: #7A828C;
  --f-display: "Bricolage Grotesque", "Segoe UI", system-ui, sans-serif; --f-body: "IBM Plex Sans", "Segoe UI", system-ui, sans-serif; --f-mono: "IBM Plex Mono", ui-monospace, Consolas, monospace;
}}
@media (prefers-color-scheme: dark) {{ :root:not([data-theme="light"]) {{ {DARK} }} }}
:root[data-theme="dark"] {{ {DARK} }}
:root[data-lang="en"] [data-l="ru"], :root[data-lang="ru"] [data-l="en"], :root:not([data-lang]) [data-l="ru"] {{ display: none; }}
:root[data-lang="ru"] h1, :root[data-lang="ru"] .type-head h2, :root[data-lang="ru"] .stats b {{ font-family: var(--f-body); font-weight: 600; letter-spacing: -.01em; }}
* {{ box-sizing: border-box; }}
[hidden] {{ display: none !important; }}
body {{ background: var(--bg); color: var(--ink); font: 15px/1.5 var(--f-body); }}
.wrap {{ max-width: 1240px; margin-inline: auto; padding-left: max(16px, env(safe-area-inset-left, 0px)); padding-right: max(16px, env(safe-area-inset-right, 0px)); padding-block: 28px 64px; display: grid; gap: 36px; }}
h1, h2, h3 {{ margin: 0; text-wrap: balance; }}
p {{ margin: 0; }}
.bar {{ display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }}
.toggles {{ display: flex; gap: 8px; flex-wrap: wrap; }}
.seg {{ display: inline-flex; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; background: var(--paper); }}
.seg button {{ border: 0; background: transparent; color: var(--muted); font: 500 13px/1 var(--f-body); padding: 8px 12px; min-height: 36px; cursor: pointer; }}
.seg button + button {{ border-left: 1px solid var(--line); }}
.seg button[aria-pressed="true"] {{ background: var(--ink); color: var(--paper); }}
.seg button:focus-visible {{ outline: 2px solid var(--accent); outline-offset: -2px; }}
.top {{ display: grid; gap: 20px; }}
.kicker {{ font: 500 12px/1 var(--f-mono); letter-spacing: .1em; text-transform: uppercase; color: var(--accent); }}
h1 {{ font: 700 clamp(2rem, 4.6vw, 3.4rem)/1.04 var(--f-display); letter-spacing: -.02em; max-width: 20ch; }}
.lede {{ color: var(--muted); max-width: 66ch; font-size: 1.05rem; }}
.stats {{ display: flex; flex-wrap: wrap; gap: 8px 28px; padding: 0; margin: 0; list-style: none; font-variant-numeric: tabular-nums; }}
.stats li {{ display: grid; }}
.stats b {{ font: 700 1.9rem/1.1 var(--f-display); }}
.stats span {{ font-size: 13px; color: var(--muted); }}
.filters {{ display: grid; gap: 10px; padding: 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--paper); }}
.frow {{ display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }}
.frow > .lbl {{ font: 500 11px/1 var(--f-mono); letter-spacing: .08em; text-transform: uppercase; color: var(--muted); min-width: 64px; }}
.chip {{ font: 500 13px/1 var(--f-body); color: var(--ink); background: transparent; border: 1px solid var(--line); border-radius: 999px; padding: 8px 12px; min-height: 36px; cursor: pointer; }}
.chip:hover {{ border-color: var(--accent); }}
.chip[aria-pressed="true"] {{ background: var(--ink); color: var(--paper); border-color: var(--ink); }}
.chip:focus-visible, .open:focus-visible {{ outline: 2px solid var(--accent); outline-offset: 2px; }}
.clear {{ font: 500 13px/1 var(--f-body); color: var(--accent); background: none; border: 0; padding: 8px 4px; cursor: pointer; text-decoration: underline; text-underline-offset: 3px; }}
.type {{ display: grid; gap: 18px; }}
.type-head {{ display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: start; gap: 16px; padding-top: 18px; border-top: 2px solid var(--ink); }}
.phase {{ font: 500 13px/1 var(--f-mono); color: var(--muted); padding-top: 6px; }}
.type-head h2 {{ font: 700 1.5rem/1.15 var(--f-display); letter-spacing: -.01em; }}
.type-head p {{ color: var(--muted); }}
.status {{ font: 500 12px/1 var(--f-mono); padding: 6px 10px; border-radius: 6px; white-space: nowrap; }}
.status.ok {{ background: var(--accent-soft); color: var(--accent); }}
.status.part {{ background: var(--warn-soft); color: var(--warn); }}
.status.none {{ border: 1px dashed var(--off); color: var(--off); }}
.grid {{ display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr)); gap: 20px; }}
.card {{ display: grid; grid-template-rows: auto 1fr; background: var(--paper); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; min-width: 0; }}
.shots {{ position: relative; aspect-ratio: 16 / 10; background: var(--bg); border-bottom: 1px solid var(--line); overflow: hidden; }}
.desk {{ display: block; width: 100%; height: 100%; object-fit: cover; object-position: top; }}
.phone {{ position: absolute; right: 10px; bottom: -28px; width: 24%; max-width: 100%; aspect-ratio: 375 / 780; border: 3px solid var(--ink); border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 6px 18px rgb(0 0 0 / .22); }}
.phone img {{ display: block; width: 138.67%; max-width: none; height: auto; }}
.blank {{ display: grid; place-items: center; background: repeating-linear-gradient(135deg, transparent 0 10px, var(--line) 10px 11px), var(--bg); }}
.blank p {{ font: 500 12px/1 var(--f-mono); letter-spacing: .08em; text-transform: uppercase; color: var(--muted); background: var(--paper); padding: 8px 12px; border-radius: 6px; }}
.body {{ display: grid; gap: 10px; align-content: start; padding: 16px; }}
.eyebrow {{ display: flex; align-items: center; flex-wrap: wrap; gap: 8px; font-weight: 600; font-size: 14px; }}
.letter {{ display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 6px; background: var(--ink); color: var(--paper); font: 500 12px/1 var(--f-mono); }}
.sample {{ font: 500 11px/1 var(--f-mono); color: var(--warn); background: var(--warn-soft); padding: 4px 6px; border-radius: 4px; }}
.card h3 {{ font: 500 1.02rem/1.35 var(--f-body); color: var(--muted); }}
.meta {{ display: flex; flex-wrap: wrap; gap: 6px; padding: 0; margin: 0; list-style: none; }}
.meta li {{ font-size: 12px; padding: 3px 8px; border-radius: 999px; background: var(--bg); color: var(--ink); }}
.foot {{ display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px; padding-top: 12px; border-top: 1px solid var(--line); }}
.stat {{ font: 500 12px/1.3 var(--f-mono); color: var(--muted); font-variant-numeric: tabular-nums; }}
.open {{ display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 0 14px; border-radius: 8px; background: var(--accent); color: var(--paper); font-weight: 600; font-size: 14px; text-decoration: none; white-space: nowrap; }}
.open:hover {{ filter: brightness(1.08); }}
.open.off {{ background: none; color: var(--off); border: 1px dashed var(--off); }}
.note {{ color: var(--muted); font-size: 14px; }}
.none-msg {{ color: var(--muted); }}
footer p {{ color: var(--muted); font-size: 13px; max-width: 72ch; }}
.viewbar {{ display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }}
#secview {{ display: grid; gap: 36px; }}
.ssel {{ font: 500 14px/1.2 var(--f-body); color: var(--ink); background: var(--paper); border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; min-height: 36px; max-width: 100%; }}
.sshare {{ font: 500 12px/1 var(--f-body); color: var(--muted); padding: 5px 9px; border: 1px solid var(--line); border-radius: 999px; }}
.sshare.many {{ color: var(--accent); border-color: var(--accent); }}
.shead h3 {{ margin-right: auto; }}
#sshown {{ margin-left: auto; }}
.sgroup {{ display: grid; gap: 20px; scroll-margin-top: 60px; }}
.scard {{ display: grid; gap: 12px; padding: 18px; background: var(--paper); border: 1px solid var(--line); border-radius: 12px; }}
.shead {{ display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px 16px; }}
.shead h3 {{ font: 700 1.25rem/1.2 var(--f-display); color: var(--ink); }}
.scount {{ font: 500 12px/1 var(--f-mono); color: var(--muted); }}
.dgrid {{ display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 16px; }}
.dtile {{ margin: 0; display: grid; gap: 8px; }}
.dtile .shots {{ display: block; border: 1px solid var(--line); border-radius: 10px; }}
.dtile figcaption {{ display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 14px; }}
.dtile figcaption b {{ text-transform: capitalize; }}
.dtile figcaption a {{ color: var(--accent); font-weight: 600; text-decoration: none; white-space: nowrap; }}
@media (max-width: 560px) {{
  .wrap {{ padding-block: 20px 48px; gap: 28px; }}
  .type-head {{ grid-template-columns: minmax(0, 1fr) auto; }}
  .phase {{ display: none; }}
  .foot {{ flex-wrap: wrap; }}
}}
@media (prefers-reduced-motion: no-preference) {{ .card {{ transition: border-color .15s; }} .card:hover {{ border-color: var(--muted); }} }}
</style>

<div class="wrap">
  <div class="bar">
    <p class="kicker">WebNets · {t("Demo gallery", "Галерея демо")}</p>
    <div class="toggles">
      <div class="seg" role="group" aria-label="Language / Язык">
        <button type="button" data-set-lang="en" aria-pressed="true" lang="en">EN</button>
        <button type="button" data-set-lang="ru" aria-pressed="false" lang="ru">RU</button>
      </div>
      <div class="seg" role="group" aria-label="Theme / Тема">
        <button type="button" data-set-theme="light" aria-pressed="true">{t("Light", "Светлая")}</button>
        <button type="button" data-set-theme="dark" aria-pressed="false">{t("Dark", "Тёмная")}</button>
        <button type="button" data-set-theme="auto" aria-pressed="false">{t("Auto", "Авто")}</button>
      </div>
    </div>
  </div>

  <header class="top">
    <h1>{t("Every site type, every demo", "Все типы сайтов, все демо")}</h1>
    <p class="lede">{t("Home pages of all demo sites built from the shared block library, shown at desktop and phone width. Brands, numbers and reviews are fictional. Open a demo to click through its pages.",
                       "Главные страницы всех демо-сайтов, собранных из общей библиотеки блоков, на компьютере и на телефоне. Бренды, цифры и отзывы вымышлены. Откройте демо, чтобы пройтись по его страницам.")}</p>
    {SETTINGS_LINK}
    <ul class="stats">
      <li><b>{n_built}</b><span>{t(f"of {n_types} site types built", f"из {n_types} типов сайтов готово")}</span></li>
      <li><b>{n_demos}</b><span>{t("demo sites", ru_plural(n_demos, "демо-сайт", "демо-сайта", "демо-сайтов"))}</span></li>
      <li><b>{n_pages}</b><span>{t("pages in total", ru_plural(n_pages, "страница всего", "страницы всего", "страниц всего"))}</span></li>
      <li><b>{len(markets)}</b><span>{t("markets: AU, KZ, BR, demo", "рынка: AU, KZ, BR, демо")}</span></li>
      <li><b>{n_langs}</b><span>{t("languages", ru_plural(n_langs, "язык", "языка", "языков"))}</span></li>
    </ul>
  </header>

  <div class="viewbar">{VIEW_SWITCH}</div>
  <div class="filters" role="group" aria-label="Filter demos / Фильтр демо">
    <div class="frow"><span class="lbl">{t("Type", "Тип")}</span>{chips_type}</div>
    <div class="frow"><span class="lbl">{t("Market", "Рынок")}</span>{chips_market}<button type="button" class="clear" id="clear" hidden>{t("Show all", "Показать все")}</button></div>
  </div>
  <p class="none-msg" id="none" hidden>{t("No demo matches these filters.", "Нет демо под эти фильтры.")} <button type="button" class="clear" id="clear2">{t("Show all", "Показать все")}</button></p>
  <div id="siteview">{''.join(sections)}</div>
  {SEC_VIEW}
  <footer><p>{t(f"Screenshots and review links show the current builds as of {BUILT_EN}.", f"Скриншоты и ссылки показывают текущие сборки на {BUILT_RU}.")}</p></footer>
</div>

<script>
(function () {{
  var docEl = document.documentElement;
  function load(k) {{ try {{ return localStorage.getItem(k); }} catch (e) {{ return null; }} }}
  function keep(k, v) {{ try {{ localStorage.setItem(k, v); }} catch (e) {{ /* private window: choice lasts for this visit */ }} }}

  // Language: #ru / #en in the link, then the saved choice, then the browser language.
  function setLang(l, save) {{
    docEl.setAttribute('data-lang', l);
    docEl.lang = l;
    document.title = l === 'ru' ? 'Галерея WebNets' : 'WebNets Gallery';
    document.querySelectorAll('img[data-alt-en]').forEach(function (img) {{ img.alt = img.getAttribute('data-alt-' + l); }});
    document.querySelectorAll('[data-set-lang]').forEach(function (b) {{ b.setAttribute('aria-pressed', String(b.dataset.setLang === l)); }});
    document.querySelectorAll('option[data-en]').forEach(function (o) {{ o.textContent = o.getAttribute('data-' + l); }});
    if (window.sxgCount) window.sxgCount();
    if (save) keep('sxg-lang', l);
  }}
  var hashLang = (location.hash.match(/^#(ru|en)$/) || [])[1];
  setLang(hashLang || load('sxg-lang') || ((navigator.language || '').toLowerCase().indexOf('ru') === 0 ? 'ru' : 'en'), false);

  // Theme: light unless the viewer picks dark, or auto (follow the device / viewer setting).
  function setTheme(th, save) {{
    if (th === 'auto') docEl.removeAttribute('data-theme'); else docEl.setAttribute('data-theme', th);
    document.querySelectorAll('[data-set-theme]').forEach(function (b) {{ b.setAttribute('aria-pressed', String(b.dataset.setTheme === th)); }});
    if (save) keep('sxg-theme', th);
  }}
  setTheme(load('sxg-theme') || 'light', false);

  document.querySelectorAll('[data-set-lang]').forEach(function (b) {{ b.addEventListener('click', function () {{ setLang(b.dataset.setLang, true); }}); }});
  document.querySelectorAll('[data-set-theme]').forEach(function (b) {{ b.addEventListener('click', function () {{ setTheme(b.dataset.setTheme, true); }}); }});

  var state = {{ type: null, market: null }};
  var chips = document.querySelectorAll('.chip[data-filter]');
  var cards = document.querySelectorAll('.card');
  var sections = document.querySelectorAll('.type');
  var clear = document.getElementById('clear');
  var none = document.getElementById('none');
  function apply() {{
    var shown = 0;
    cards.forEach(function (c) {{
      var ok = (!state.type || c.dataset.type === state.type) && (!state.market || c.dataset.market === state.market);
      c.hidden = !ok; if (ok) shown++;
    }});
    sections.forEach(function (s) {{ s.hidden = !s.querySelector('.card:not([hidden])'); }});
    chips.forEach(function (b) {{ b.setAttribute('aria-pressed', String(state[b.dataset.filter] === b.dataset.value)); }});
    clear.hidden = !state.type && !state.market;
    none.hidden = shown > 0;
  }}
  chips.forEach(function (b) {{
    b.addEventListener('click', function () {{
      var k = b.dataset.filter;
      state[k] = state[k] === b.dataset.value ? null : b.dataset.value;
      apply();
    }});
  }});
  function reset() {{ state = {{ type: null, market: null }}; apply(); }}
  var filters = document.querySelector('.filters'), siteview = document.getElementById('siteview'), secview = document.getElementById('secview');
  function setView(v, save) {{
    if (!secview) return;
    var sec = v === 'sections';
    secview.hidden = !sec; siteview.hidden = sec; filters.hidden = sec; none.hidden = true;
    document.querySelectorAll('[data-view]').forEach(function (b) {{ b.setAttribute('aria-pressed', String(b.dataset.view === v)); }});
    if (save) keep('sxg-view', v);
    if (!sec) apply();
  }}
  document.querySelectorAll('[data-view]').forEach(function (b) {{ b.addEventListener('click', function () {{ setView(b.dataset.view, true); }}); }});
  setView(/^#(sections|s-)/.test(location.hash) ? 'sections' : (load('sxg-view') || 'sites'), false);
  clear.addEventListener('click', reset);
  // "By section" filters: site type, section type (group), one section, shared or one type only
  var sst = {{ type: null, group: null, share: null, block: '' }}, sel = document.getElementById('sblock');
  function fits(c, skipBlock) {{
    return (!sst.type || c.dataset.types.indexOf(' ' + sst.type + ' ') >= 0) && (!sst.group || c.dataset.group === sst.group)
      && (!sst.share || c.dataset.share === sst.share) && (skipBlock || !sst.block || c.dataset.block === sst.block);
  }}
  function sapply() {{
    var shown = 0, designs = 0;
    document.querySelectorAll('.scard').forEach(function (c) {{
      var ok = fits(c, false);
      c.hidden = !ok; if (ok) {{ shown++; designs += c.querySelectorAll('.dtile').length; }}
    }});
    document.querySelectorAll('.sgroup').forEach(function (g) {{
      var n = g.querySelectorAll('.scard:not([hidden])').length;
      g.hidden = !n; g.querySelector('[data-gcount]').textContent = (n < 10 ? '0' : '') + n;
    }});
    Array.prototype.forEach.call(sel.options, function (o) {{
      if (o.value) o.hidden = !fits(document.querySelector('.scard[data-block="' + o.value + '"]'), true);
    }});
    document.querySelectorAll('[data-sfilter]').forEach(function (b) {{ b.setAttribute('aria-pressed', String(sst[b.dataset.sfilter] === b.dataset.value)); }});
    document.getElementById('snone').hidden = shown > 0;
    document.getElementById('sclear').hidden = !sst.type && !sst.group && !sst.share && !sst.block;
    window.sxgShown = [shown, designs]; window.sxgCount();
  }}
  window.sxgCount = function () {{
    var s = window.sxgShown, el = document.getElementById('sshown');
    if (!s || !el) return;
    el.textContent = docEl.lang === 'ru' ? 'Секций: ' + s[0] + ', дизайнов: ' + s[1] : s[0] + ' sections, ' + s[1] + ' designs';
  }};
  if (sel) {{
    document.querySelectorAll('[data-sfilter]').forEach(function (b) {{
      b.addEventListener('click', function () {{
        var k = b.dataset.sfilter;
        sst[k] = sst[k] === b.dataset.value ? null : b.dataset.value;
        if (sst.block && !fits(document.querySelector('.scard[data-block="' + sst.block + '"]'), true)) {{ sst.block = ''; sel.value = ''; }}
        sapply();
      }});
    }});
    sel.addEventListener('change', function () {{ sst.block = sel.value; sapply(); }});
    document.getElementById('sclear').addEventListener('click', function () {{ sst = {{ type: null, group: null, share: null, block: '' }}; sel.value = ''; sapply(); }});
    sapply();
  }}
  document.getElementById('clear2').addEventListener('click', reset);
}})();
</script>
'''
out = os.path.join(root, 'dist/index.html' if LOCAL else 'dist/gallery/gallery.html')
open(out, 'w', encoding='utf-8').write(page)
missing = [d['id'] for d in demos if not d['link']]
print(len(demos), 'demos', n_pages, 'pages', round(os.path.getsize(out) / 1024, 1), 'KB', '| skipped (no review link or screenshots):', skipped or 'none')
