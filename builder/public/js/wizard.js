// New-site wizard: type → market → brand → angle → style → where. Only valid choices can be picked; blocked ones say why.
// The engine still has the last word: the editor shows any build error the moment the site opens.
import { api, h, modal, clone, label, toast } from './dom.js';
import { store } from './main.js';

const GATE = { live: 'sports', 'live-casino': 'casino' };
const TYPE_INFO = {
  landing: 'Sells the brand on one page', app: 'Store-style app download page', promo: 'Current offers and codes',
  vendor: 'One game studio at one brand', satellite: 'Home plus service pages', reviews: 'Player reviews of one brand',
  casinos: 'Ranked reviews of several casinos', news: 'News and guides with labelled PR',
};

const publisherTypes = (t) => !!(t.compares_brands || t.articles);

/** Why a type cannot be built in a market (null = it can). */
function typeBlock(meta, typeId, m) {
  const t = meta.types[typeId];
  if (t.requires_vertical && !m.verticals[t.requires_vertical]) return `${m.name} does not allow ${t.requires_vertical}`;
  const promoTypes = Object.values(t.angles).flatMap((a) => a.requires_promo || []);
  if (promoTypes.length && !promoTypes.some((p) => m.promotions_allowed.includes(p))) return `No promotion type is cleared in ${m.name}`;
  if (t.requires_reviews && !Object.values(meta.reviews).some((r) => r[m.id])) return `No review feed for ${m.name} yet`;
  if (t.compares_brands && (meta.compared[m.id] || []).length < 3) return `Fewer than 3 brands with facts and ratings in ${m.name}`;
  if (publisherTypes(t) && !Object.values(meta.publishers).some((p) => p.markets.includes(m.id))) return `No publisher set up for ${m.name}`;
  return null;
}

/** Why a brand cannot be used for a type in a market. */
function brandBlock(meta, typeId, m, b) {
  const t = meta.types[typeId];
  if (!b.markets.includes(m.id) || !b.licensed) return `Not licensed in ${m.name}`;
  const allowed = b.verticals.filter((v) => m.verticals[GATE[v] || v]);
  if (!allowed.length) return `None of its products are allowed in ${m.name}`;
  if (t.requires_vertical && !b.verticals.includes(t.requires_vertical)) return `Does not offer ${t.requires_vertical}`;
  if (t.requires_provider && !(meta.catalogs[b.id]?.[m.id] || []).length) return `No game catalog for ${m.name}`;
  if (t.requires_reviews && !meta.reviews[b.id]?.[m.id]) return `No published reviews for ${m.name}`;
  if ((t.sections || []).some((s) => s.block === 'app-hero')) return installBlock(m, b);
  return null;
}

/**
 * App pages need an install path for iPhone and Android visitors. Same rule as sx_app_platforms() in lib/app.php:
 * the market decides which paths count (KZ: APK for Android), the brand's app listing decides which exist.
 */
function installBlock(m, b) {
  const android = m.apps?.android || 'play';
  const want = [...(m.apps?.ios === 'app-store' ? ['ios'] : []), ...(['play', 'both'].includes(android) ? ['android'] : []), ...(['apk', 'both'].includes(android) ? ['apk'] : [])];
  const have = want.filter((p) => b.app.includes(p));
  if (!b.app.length) return 'Has no app listing';
  if (!have.length) return `No install path in ${m.name}`;
  const androidWant = want.filter((p) => p !== 'ios');
  if (androidWant.length && !have.some((p) => p !== 'ios')) {
    return `No Android install in ${m.name}: it needs ${androidWant.map((p) => (p === 'apk' ? 'an APK' : 'Google Play')).join(' or ')}`;
  }
  return null;
}

function angleBlock(meta, typeId, m, brandId, angleId) {
  const a = meta.types[typeId].angles[angleId];
  if (a.requires_vertical && !m.verticals[a.requires_vertical]) return `Needs ${a.requires_vertical}`;
  if (a.requires_apk && !(meta.brands[brandId]?.app.includes('apk') && m.apps?.android !== 'play')) return 'Needs an APK download for this brand and market';
  if (a.requires_promo && !a.requires_promo.some((p) => m.promotions_allowed.includes(p))) return 'Its offers are not cleared here';
  return null;
}

export function openWizard(preset = {}) {
  const meta = store.meta;
  const projects = [...new Set(meta.sites.map((s) => s.project))];
  const st = { type: preset.type || null, market: null, brand: null, publisher: null, brands: [], provider: null, articles: null,
    angle: '', preset: null, project: preset.project || projects[0] || 'project-1', newProject: '', id: '', idTouched: false, start: 'blank' };
  const body = h('div', { style: { display: 'grid', gap: '18px' } });
  const createBtn = h('button', { class: 'btn btn--primary', type: 'button' }, 'Create site');
  const status = h('span', { class: 'muted', style: { marginRight: 'auto' } });
  const dlg = modal('New site', body, [status, h('button', { class: 'btn', type: 'button', onclick: () => dlg.close() }, 'Cancel'), createBtn]);
  createBtn.onclick = () => create(st, dlg, createBtn);

  const radio = (name, value, checked, disabled, onPick, title, sub, why, extra) => h('label', { class: 'choice' },
    h('input', { type: 'radio', name, value, checked, disabled: !!disabled, onchange: () => { onPick(value); draw(); } }),
    h('b', null, title), sub && h('small', null, sub), why && h('small', { class: 'why' }, why), extra);

  function suggestPreset() {
    const used = meta.sites.filter((s) => s.project === projectName() && s.type === st.type).map((s) => s.preset);
    const kind = st.brand ? meta.brands[st.brand]?.kind : 'casino';
    const free = Object.values(meta.presets).filter((p) => !used.includes(p.id));
    return (free.find((p) => p.fits.some((f) => f === kind || f === st.type)) || free[0] || Object.values(meta.presets)[0]).id;
  }
  const projectName = () => (st.project === '__new' ? st.newProject.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-') : st.project);

  function draw() {
    const m = meta.markets[st.market];
    const t = meta.types[st.type];
    // keep selections valid as earlier choices change
    if (m && t && typeBlock(meta, st.type, m)) st.market = null;
    if (st.brand && (!m || brandBlock(meta, st.type, m, meta.brands[st.brand]))) st.brand = null;
    if (!st.preset && st.type) st.preset = suggestPreset();
    if (!st.idTouched && st.type && st.market) {
      const who = st.brand || st.publisher || 'site';
      st.id = `${who}-${st.market}-${st.type}`;
      let n = 2;
      while (meta.sites.some((s) => s.path === `sites/${projectName()}/${st.id}.json`)) st.id = `${who}-${st.market}-${st.type}-${n++}`;
    }

    const steps = [];
    steps.push(h('div', { class: 'step' }, h('h3', null, '1 · Site type'),
      h('div', { class: 'choices' }, Object.entries(meta.types).map(([id, ty]) =>
        radio('type', id, st.type === id, false, (v) => { st.type = v; st.preset = null; st.angle = ''; }, ty.title, TYPE_INFO[id])))));

    if (t) {
      steps.push(h('div', { class: 'step' }, h('h3', null, '2 · Market'),
        h('div', { class: 'choices' }, Object.values(meta.markets).map((mk) => {
          const why = typeBlock(meta, st.type, mk);
          return radio('market', mk.id, st.market === mk.id, why, (v) => { st.market = v; }, mk.name,
            `${mk.locales.join(', ').toUpperCase()} · ${mk.min_age}+${mk.demo_only ? ' · demos only' : ''}`, why);
        }))));
    }

    if (t && m) {
      if (publisherTypes(t)) {
        const pubs = Object.entries(meta.publishers).filter(([, p]) => p.markets.includes(m.id));
        if (!st.publisher || !pubs.some(([id]) => id === st.publisher)) st.publisher = pubs[0]?.[0] || null;
        steps.push(h('div', { class: 'step' }, h('h3', null, '3 · Publisher (the site is branded as the publisher)'),
          h('div', { class: 'choices' }, pubs.map(([id, p]) => radio('publisher', id, st.publisher === id, false, (v) => { st.publisher = v; }, p.name)))));
      }
      if (t.compares_brands) {
        const ok = meta.compared[m.id] || [];
        if (!st.brands.length) st.brands = [...ok];
        steps.push(h('div', { class: 'step' }, h('h3', null, `Brands to compare (${st.brands.length} chosen, at least 3)`),
          h('div', { class: 'choices' }, Object.values(meta.brands).map((b) => {
            const why = brandBlock(meta, st.type, m, b) || (!ok.includes(b.id) ? `No facts or rating for ${m.name}` : null);
            return h('label', { class: 'choice' }, h('input', { type: 'checkbox', checked: st.brands.includes(b.id), disabled: !!why,
              onchange: (e) => { st.brands = e.target.checked ? [...st.brands, b.id] : st.brands.filter((x) => x !== b.id); draw(); } }),
            h('b', null, b.name), why && h('small', { class: 'why' }, why));
          }))));
      } else if (!publisherTypes(t)) {
        steps.push(h('div', { class: 'step' }, h('h3', null, '3 · Brand'),
          h('div', { class: 'choices' }, Object.values(meta.brands).map((b) => {
            const why = brandBlock(meta, st.type, m, b);
            return radio('brand', b.id, st.brand === b.id, why, (v) => { st.brand = v; st.provider = null; }, b.name, label(b.kind), why,
              h('span', { class: 'swatches' }, h('i', { style: { background: b.color } })));
          }))));
      }
      if (t.requires_provider && st.brand) {
        const provs = meta.catalogs[st.brand]?.[m.id] || [];
        if (!provs.includes(st.provider)) st.provider = provs[0] || null;
        steps.push(h('div', { class: 'step' }, h('h3', null, 'Game studio'),
          h('div', { class: 'choices' }, provs.map((p) => radio('provider', p, st.provider === p, false, (v) => { st.provider = v; }, meta.providers[p] || p)))));
      }
      if (t.articles) {
        const dirs = Object.entries(meta.articles);
        if (!st.articles) st.articles = dirs[0]?.[0] || null;
        steps.push(h('div', { class: 'step' }, h('h3', null, 'Articles folder'),
          h('div', { class: 'choices' }, dirs.map(([d, n]) => radio('articles', d, st.articles === d, false, (v) => { st.articles = v; }, d.split('/')[1], `${n} article files`)))));
      }
    }

    const ready = t && m && (publisherTypes(t) ? st.publisher : st.brand) && (!t.compares_brands || st.brands.length >= 3) && (!t.requires_provider || st.provider);
    if (ready) {
      steps.push(h('div', { class: 'step' }, h('h3', null, '4 · Angle (what the site leads with)'),
        h('div', { class: 'choices' },
          radio('angle', '', st.angle === '', false, (v) => { st.angle = v; }, 'Let the seed choose', 'Picks an angle this market allows'),
          Object.keys(t.angles).map((a) => {
            const why = angleBlock(meta, st.type, m, st.brand, a);
            return radio('angle', a, st.angle === a, why, (v) => { st.angle = v; }, label(a), null, why);
          }))));

      const used = meta.sites.filter((s) => s.project === projectName() && s.type === st.type).map((s) => s.preset);
      steps.push(h('div', { class: 'step' }, h('h3', null, '5 · Style'),
        h('div', { class: 'choices' }, Object.values(meta.presets).map((p) => radio('preset', p.id, st.preset === p.id, false, (v) => { st.preset = v; }, p.name,
          used.includes(p.id) ? 'Already used by this type in the project' : p.mood.split('.')[0], null,
          h('span', { class: 'swatches' }, ['bg', 'surface', 'accent', 'text'].map((k) => h('i', { style: { background: p.color[k] } }))))))));

      const demos = meta.sites.filter((s) => s.type === st.type);
      steps.push(h('div', { class: 'step' }, h('h3', null, '6 · Where and how to start'),
        h('div', { class: 'grid2' },
          h('label', { class: 'field' }, h('span', null, 'Project'),
            h('select', { onchange: (e) => { st.project = e.target.value; draw(); } },
              projects.map((p) => h('option', { value: p, selected: st.project === p }, p)), h('option', { value: '__new', selected: st.project === '__new' }, 'New project…'))),
          st.project === '__new'
            ? h('label', { class: 'field' }, h('span', null, 'New project name'), h('input', { type: 'text', value: st.newProject, placeholder: 'client-x', oninput: (e) => { st.newProject = e.target.value; } }))
            : h('span'),
          h('label', { class: 'field' }, h('span', null, 'Site id'), h('input', { type: 'text', value: st.id, oninput: (e) => { st.id = e.target.value; st.idTouched = true; } }),
            h('small', null, 'Lowercase letters, digits and dashes. Becomes the file name and the dist folder.'))),
        h('div', { class: 'choices' },
          radio('start', 'blank', st.start === 'blank', !t.template, (v) => { st.start = v; }, 'Blank template', 'Fields marked TODO show what to fill', !t.template && 'No template for this type'),
          demos.map((d) => radio('start', d.path, st.start === d.path, false, (v) => { st.start = v; }, `Copy ${d.id}`, 'Start from a demo’s content and settings')))));
    }
    body.replaceChildren(...steps);
    createBtn.disabled = !ready;
    status.textContent = ready ? `sites/${projectName()}/${st.id}.json` : 'Pick a type, a market and a brand to continue.';
  }
  draw();
}

async function create(st, dlg, btn) {
  const meta = store.meta;
  const project = st.project === '__new' ? st.newProject.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-') : st.project;
  if (!/^[a-z0-9-]+$/.test(project || '') || !/^[a-z0-9-]+$/.test(st.id)) { toast('Project and site id may use only lowercase letters, digits and dashes.'); return; }
  btn.disabled = true;
  const m = meta.markets[st.market];
  const src = st.start === 'blank' ? (await api.get('template?type=' + st.type)).config : (await api.get('site?path=' + encodeURIComponent(st.start))).config;
  const cfg = clone(src);
  delete cfg._howto; delete cfg._demo;
  const sameBrand = st.start !== 'blank' && cfg.brand === st.brand && cfg.market === st.market;
  Object.assign(cfg, { id: st.id, type: st.type, market: st.market, locale: m.default_locale, preset: st.preset, seed: `${st.id}-${Math.random().toString(36).slice(2, 7)}`, prefix: 'auto' });
  if (st.angle) cfg.angle = st.angle; else delete cfg.angle;
  if (m.demo_only || (st.start !== 'blank' && src.demo)) cfg.demo = true;
  const host = (b) => (m.domain_suffix ? `${b}${m.domain_suffix}` : `${b}.example`);
  const t = meta.types[st.type];
  if (publisherTypes(t)) {
    cfg.publisher = st.publisher; delete cfg.brand;
    const pr = t.compares_brands ? st.brands : Object.values(meta.brands).filter((b) => !brandBlock(meta, 'landing', m, b)).map((b) => b.id);
    if (t.compares_brands) cfg.brands = st.brands;
    if (t.articles) cfg.articles = st.articles;
    cfg.links = { brands: Object.fromEntries(pr.map((b) => [b, cfg.links?.brands?.[b] || `https://${host(b)}/join?src=${st.id}`])) };
    if (cfg.featured && !st.brands.includes(cfg.featured)) delete cfg.featured;
  } else {
    cfg.brand = st.brand;
    if (st.provider) cfg.provider = st.provider;
    if (!sameBrand) {
      const links = { go: `https://${host(st.brand)}/join?src=${st.id}` };
      for (const k of ['ios', 'android', 'apk', 'qr']) if (cfg.links?.[k]) links[k] = `https://${host(st.brand)}/app/${k}?src=${st.id}`;
      if (cfg.links?.game) links.game = `https://${host(st.brand)}/game/{game}?src=${st.id}`;
      if (cfg.links?.provider) links.provider = `https://${host(st.brand)}/studio/{provider}?src=${st.id}`;
      cfg.links = links;
    }
  }
  if (cfg.site?.url) cfg.site.url = `https://${st.id}.example`;
  const path = `sites/${project}/${st.id}.json`;
  const r = await api.post('save', { path, config: cfg, create: true });
  btn.disabled = false;
  if (!r.ok) { toast(r.error); return; }
  dlg.close();
  location.hash = '#/edit/' + encodeURIComponent(path);
}
