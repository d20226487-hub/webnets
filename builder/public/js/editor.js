// Editor: tabbed side panel (Structure, Content, Style, X-ray, JSON) + live preview from the real engine.
import { api, h, clone, toast, modal, label } from './dom.js';
import { store, reloadMeta } from './main.js';
import { fieldForm } from './forms.js';
import { diversityTable } from './projects.js';

const FRAME = ['header', 'footer', 'sticky-cta', 'age-gate'];
const DEVICES = { phone: [375, 'Phone'], tablet: [768, 'Tablet'], desktop: [1280, 'Desktop'] };
let S = null;

// ---------- leaving the editor ----------
window.addEventListener('beforeunload', (e) => { if (S?.dirty) { e.preventDefault(); e.returnValue = ''; } });

export function leaveEditor() {
  if (!S) return true;
  const target = decodeURIComponent((location.hash || '').slice(7));
  if (location.hash.startsWith('#/edit/') && target === S.path) {
    if (S.stay) { S.stay = false; return false; }
    return true;
  }
  if (S.dirty && !S.discard) {
    // Stay on the editor and ask in the page (native confirm dialogs are blocked in some views).
    const dest = location.hash;
    S.stay = true;
    location.hash = '#/edit/' + encodeURIComponent(S.path);
    const go = () => { S.discard = true; dlg.close(); location.hash = dest; };
    const dlg = modal('Unsaved changes', h('p', null, `${S.path} has changes that are not saved yet.`), [
      h('button', { class: 'btn', type: 'button', onclick: () => dlg.close() }, 'Stay'),
      h('button', { class: 'btn btn--danger', type: 'button', onclick: go }, 'Discard changes'),
      h('button', { class: 'btn btn--primary', type: 'button', onclick: async () => { if (await save()) go(); } }, 'Save and leave')]);
    return false;
  }
  window.removeEventListener('message', onFrameMessage);
  document.removeEventListener('keydown', onKey);
  S.observer?.disconnect();
  S = null;
  return true;
}

// ---------- config access (one-page vs multi-page layouts of the same keys) ----------
const bp = () => store.meta.types[S.cfg.type];
const multi = () => !!bp().pages;
const pageSections = (page) => (multi() ? bp().pages[page]?.sections || [] : bp().sections);
const isFrame = (sid) => FRAME.includes(sid);
function obj(root, ...keys) { let o = root; for (const k of keys) { if (typeof o[k] !== 'object' || o[k] === null || Array.isArray(o[k])) o[k] = {}; o = o[k]; } return o; }
const pinsFor = (page, sid) => (isFrame(sid) || !multi() ? obj(S.cfg, 'variants') : obj(S.cfg, 'variants', page));
const contentFor = (page, sid) => (isFrame(sid) || !multi() ? S.cfg.content?.[sid] : S.cfg.content?.[page]?.[sid]) || {};
function setContent(page, sid, val) {
  const box = isFrame(sid) || !multi() ? obj(S.cfg, 'content') : obj(S.cfg, 'content', page);
  if (val && Object.keys(val).length) box[sid] = val; else delete box[sid];
}
const forceFor = (page) => (multi() ? obj(S.cfg, 'sections', page) : obj(S.cfg, 'sections'));
const metaFor = (page) => (multi() ? obj(S.cfg, 'meta', page) : obj(S.cfg, 'page'));
function setOrder(page, ids) { if (multi()) obj(S.cfg, 'order')[page] = ids; else S.cfg.order = ids; }
const tracePage = () => (S.last?.trace?.[multi() ? S.page : ''] || {});

// ---------- changes, history, rendering ----------
function change(fn, { redraw = true } = {}) {
  const now = Date.now();
  if (now - (S.lastPush || 0) > 700) { S.history.push(JSON.stringify(S.cfg)); if (S.history.length > 80) S.history.shift(); S.future = []; }
  S.lastPush = now;
  fn(S.cfg);
  markDirty();
  scheduleRender();
  if (redraw) drawTab();
}
function markDirty() {
  S.dirty = JSON.stringify(S.cfg) !== S.saved;
  S.el.title.classList.toggle('dirty', S.dirty);
  S.el.undo.disabled = !S.history.length; S.el.redo.disabled = !S.future.length;
}
function undo() { if (!S.history.length) return; S.future.push(JSON.stringify(S.cfg)); S.cfg = JSON.parse(S.history.pop()); S.lastPush = 0; markDirty(); scheduleRender(0); drawTab(); }
function redo() { if (!S.future.length) return; S.history.push(JSON.stringify(S.cfg)); S.cfg = JSON.parse(S.future.pop()); S.lastPush = 0; markDirty(); scheduleRender(0); drawTab(); }

function scheduleRender(ms = 350) { clearTimeout(S.timer); S.timer = setTimeout(render, ms); S.el.busy.hidden = false; }

async function render() {
  const seq = ++S.seq;
  let r;
  try { r = await api.post('render', { config: S.cfg, page: S.previewPage, gate: S.gate }); } catch (e) { r = { ok: false, error: String(e) }; }
  if (!S || seq !== S.seq) return;
  S.el.busy.hidden = true;
  S.last = { ...S.last, ...r, trace: r.trace || S.last?.trace };
  if (!r.ok) {
    S.el.error.hidden = false;
    S.el.error.replaceChildren(h('span', null, 'This configuration does not build'), h('small', null, r.error), h('small', null, 'The preview shows the last version that worked. Undo (Ctrl+Z) or change the setting the message names.'));
    S.el.frame.style.opacity = '.35';
  } else {
    S.el.error.hidden = true;
    S.el.frame.style.opacity = '1';
    S.previewPage = r.page;
    const keepY = S.shownPage === r.page ? S.el.frame.contentWindow?.scrollY || 0 : 0;
    S.shownPage = r.page;
    S.el.frame.onload = () => { try { S.el.frame.contentWindow.scrollTo(0, keepY); } catch (_) { /* ignore */ } };
    S.el.frame.srcdoc = r.html;
    const base = r.pages.find((p) => p.id === r.page)?.base;
    if (multi() && base && base !== S.page) S.page = base;
  }
  drawStageBar();
  drawBadges();
  if (['structure', 'style', 'xray'].includes(S.tab)) drawTab();
  clearTimeout(S.divTimer);
  S.divTimer = setTimeout(checkDiversity, 1200);
}

async function checkDiversity() {
  if (!S) return;
  const project = S.path.split('/')[1];
  const d = await api.post('diversity', { project, path: S.path, config: S.cfg });
  if (!S) return;
  S.diversity = d;
  drawBadges();
}

// ---------- mount ----------
export async function renderEditor(app, path) {
  const r = await api.get('site?path=' + encodeURIComponent(path));
  if (!r.ok) { app.replaceChildren(h('div', { class: 'page' }, h('h1', null, 'Site not found'), h('p', { class: 'muted' }, r.error), h('a', { href: '#/' }, 'Back to projects'))); return; }
  const isMulti = !!store.meta.types[r.config.type]?.pages;
  S = { path, cfg: r.config, saved: JSON.stringify(r.config), dirty: false, history: [], future: [], seq: 0, tab: 'structure',
    page: isMulti ? 'home' : '', previewPage: null, section: null, device: 'desktop', gate: false, fold: true, last: null, el: {} };
  const e = S.el;
  e.title = h('b', null, r.config.id);
  e.undo = h('button', { class: 'btn btn--sm', type: 'button', onclick: undo, disabled: true, title: 'Undo (Ctrl+Z)' }, '↶ Undo');
  e.redo = h('button', { class: 'btn btn--sm', type: 'button', onclick: redo, disabled: true, title: 'Redo (Ctrl+Shift+Z)' }, '↷ Redo');
  e.badges = h('div', { class: 'row' });
  e.tabs = h('div', { class: 'tabs', role: 'tablist' });
  e.body = h('div', { class: 'tabbody', role: 'tabpanel' });
  e.stagebar = h('div', { class: 'stagebar' });
  e.frame = h('iframe', { title: 'Live preview', sandbox: 'allow-scripts allow-same-origin' });
  e.fold = h('div', { class: 'fold', hidden: true }, h('span', null, 'fold · 667px'));
  e.device = h('div', { class: 'device' }, e.frame, e.fold);
  e.scaler = h('div', null, e.device);
  e.error = h('div', { class: 'stage-error', role: 'alert', hidden: true });
  e.busy = h('div', { class: 'busy' }, 'Rendering…');
  e.viewport = h('div', { class: 'viewport' }, e.error, e.busy, e.scaler);

  app.replaceChildren(h('div', { class: 'editor' },
    h('div', { class: 'ebar' },
      h('a', { class: 'btn btn--sm btn--ghost', href: '#/' }, '← Projects'),
      h('div', { class: 'ebar__title' }, e.title, h('span', null, path)),
      e.badges, e.undo, e.redo,
      h('button', { class: 'btn btn--sm', type: 'button', onclick: exportDialog, title: 'Download the site as a zip' }, 'Export'),
      h('button', { class: 'btn btn--sm', type: 'button', onclick: build, title: 'Save and build into dist/sites' }, 'Build'),
      h('button', { class: 'btn btn--sm btn--primary', type: 'button', onclick: save, title: 'Save (Ctrl+S)' }, 'Save')),
    h('aside', { class: 'panel' }, e.tabs, e.body),
    h('section', { class: 'stage', 'aria-label': 'Preview' }, e.stagebar, e.viewport)));

  window.addEventListener('message', onFrameMessage);
  document.addEventListener('keydown', onKey);
  S.observer = new ResizeObserver(layoutDevice);
  S.observer.observe(e.viewport);
  drawTabs(); drawTab(); drawStageBar(); layoutDevice();
  await render();
}

function onKey(ev) {
  if (!S) return;
  const mod = ev.ctrlKey || ev.metaKey;
  const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
  if (mod && ev.key.toLowerCase() === 's') { ev.preventDefault(); save(); }
  else if (mod && !typing && ev.key.toLowerCase() === 'z') { ev.preventDefault(); ev.shiftKey ? redo() : undo(); }
  else if (mod && !typing && ev.key.toLowerCase() === 'y') { ev.preventDefault(); redo(); }
}

function onFrameMessage(ev) {
  if (!S || ev.source !== S.el.frame.contentWindow || !ev.data?.sxNav) return;
  const href = String(ev.data.sxNav);
  if (/^(https?:|mailto:|tel:|\/\/)/.test(href)) { toast(`Outbound link (not opened in the preview): ${href}`); return; }
  const file = href.replace(/^\.\//, '').replace(/[?#].*$/, '') || 'index.html';
  const p = S.last?.pages?.find((x) => x.file === file);
  if (p) { S.previewPage = p.id; scheduleRender(0); } else toast(`Link to ${href}: not a page of this site.`);
}

// ---------- save / build ----------
async function save() {
  const r = await api.post('save', { path: S.path, config: S.cfg });
  if (!r.ok) { toast(r.error); return false; }
  S.saved = JSON.stringify(S.cfg); markDirty();
  toast(`Saved ${S.path}`);
  reloadMeta();
  return true;
}

async function build() {
  if (S.dirty && !(await save())) return;
  const r = await api.post('build', { path: S.path });
  if (!r.ok) { modal('Build failed', h('p', null, r.error)); return; }
  modal(`Built ${r.id}`, [
    h('p', null, `${r.files.length} file${r.files.length === 1 ? '' : 's'} written to dist/sites/${r.id}/.`),
    r.warnings.length ? h('ul', { class: 'warnlist' }, r.warnings.map((w) => h('li', null, h('span', { class: 'pill pill--warn' }, 'warning'), h('span', null, w), h('span')))) : h('p', null, h('span', { class: 'pill pill--ok' }, 'No warnings'), ' Ready to ship.'),
    h('table', { class: 'xt' }, h('tbody', null, r.files.map((f) => h('tr', null, h('td', { class: 'mono' }, f.file), h('td', { class: 'mono' }, `${f.kb} KB`))))),
  ], h('a', { class: 'btn btn--primary', href: r.url, target: '_blank', rel: 'noopener' }, 'Open built site ↗'));
  reloadMeta();
}

// ---------- top bar badges ----------
function drawBadges() {
  const r = S.last || {};
  const budget = store.meta.budget_kb;
  const nw = r.ok === false ? 1 : (r.warnings?.length || 0);
  const warnB = h('button', { class: 'badge ' + (r.ok === false ? 'bad' : nw ? 'warn' : 'ok'), type: 'button', onclick: () => { S.tab = 'xray'; drawTabs(); drawTab(); } },
    h('i', { class: 'dot' }), r.ok === false ? 'Build error' : nw ? `${nw} warning${nw > 1 ? 's' : ''}` : 'No warnings');
  const kb = r.kb || 0;
  const kbB = h('button', { class: 'badge ' + (kb > budget ? 'bad' : kb > budget * 0.9 ? 'warn' : 'ok'), type: 'button', title: 'Largest page weight vs. budget', onclick: () => { S.tab = 'xray'; drawTabs(); drawTab(); } },
    h('i', { class: 'dot' }), `${kb} / ${budget} KB`);
  const items = [warnB, kbB];
  const d = S.diversity;
  if (d?.ok) {
    const mine = d.pairs.filter((p) => p.a === S.path || p.b === S.path);
    const fail = mine.filter((p) => !p.ok);
    const max = Math.max(0, ...mine.map((p) => p.overlap));
    items.push(h('button', { class: 'badge ' + (fail.length ? 'bad' : max > 60 ? 'warn' : 'ok'), type: 'button', title: 'Similarity to other sites of this type in the project',
      onclick: () => diversityDialog(d, mine) },
      h('i', { class: 'dot' }), mine.length ? (fail.length ? `Too similar to ${fail.length}` : `Overlap ${max}%`) : 'Unique type in project'));
  }
  S.el.badges.replaceChildren(...items);
}

// ---------- stage ----------
function drawStageBar() {
  const r = S.last;
  const pages = r?.pages || [];
  S.el.stagebar.replaceChildren(
    pages.length > 1 && h('label', { class: 'row' }, h('span', { class: 'muted' }, 'Page'),
      h('select', { onchange: (e) => { S.previewPage = e.target.value; scheduleRender(0); } },
        pages.map((p) => h('option', { value: p.id, selected: p.id === r.page }, `${p.nav} · ${p.file} · ${p.kb} KB`)))),
    h('div', { class: 'seg', role: 'group', 'aria-label': 'Device width' }, Object.entries(DEVICES).map(([k, [w, n]]) =>
      h('button', { type: 'button', 'aria-pressed': String(S.device === k), onclick: () => { S.device = k; drawStageBar(); layoutDevice(); } }, `${n} ${w}`))),
    h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: S.fold, onchange: (e) => { S.fold = e.target.checked; layoutDevice(); } }), 'Fold line'),
    h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: S.gate, onchange: (e) => { S.gate = e.target.checked; scheduleRender(0); } }), 'Show age gate'),
    h('span', { class: 'muted', style: { marginLeft: 'auto' } }, S.scaleText || ''));
}

function layoutDevice() {
  if (!S) return;
  const vp = S.el.viewport;
  const w = DEVICES[S.device][0];
  const availW = vp.clientWidth - 32, availH = vp.clientHeight - 32;
  const s = Math.min(1, availW / w);
  const hgt = S.device === 'phone' ? Math.min(812, availH / s) : availH / s;
  Object.assign(S.el.device.style, { width: w + 'px', height: hgt + 'px', transform: `scale(${s})`, transformOrigin: 'top left' });
  Object.assign(S.el.scaler.style, { width: w * s + 'px', height: hgt * s + 'px' });
  S.el.fold.hidden = !(S.fold && S.device === 'phone');
  S.el.fold.style.top = '667px';
  const txt = s < 1 ? `Scaled to ${Math.round(s * 100)}%` : '';
  if (txt !== S.scaleText) { S.scaleText = txt; drawStageBar(); }
}

// ---------- tabs ----------
const TABS = [['structure', 'Structure'], ['content', 'Content'], ['style', 'Style'], ['xray', 'X-ray'], ['json', 'JSON']];
function drawTabs() {
  S.el.tabs.replaceChildren(...TABS.map(([k, n]) => h('button', { type: 'button', role: 'tab', 'aria-selected': String(S.tab === k), onclick: () => { S.tab = k; drawTabs(); drawTab(); } }, n)));
}
function drawTab() {
  if (!S) return;
  const focusKey = document.activeElement?.dataset?.key;
  const scroll = S.el.body.scrollTop;
  const views = { structure: viewStructure, content: viewContent, style: viewStyle, xray: viewXray, json: viewJson };
  S.el.body.replaceChildren(...[views[S.tab]()].flat());
  S.el.body.scrollTop = scroll;
  if (focusKey) S.el.body.querySelector(`[data-key="${CSS.escape(focusKey)}"]`)?.focus();
}

// ---------- Structure ----------
function viewStructure() {
  const meta = store.meta;
  const cfg = S.cfg;
  const t = bp();
  const tr = S.last?.trace || {};
  const angle = tr._site?.angle;
  const m = meta.markets[cfg.market];
  const who = cfg.publisher ? meta.publishers[cfg.publisher]?.name : meta.brands[cfg.brand]?.name;
  const out = [];

  out.push(h('div', { class: 'group' }, h('h3', null, 'Site'),
    h('p', null, h('b', null, t.title), h('span', { class: 'muted' }, ` · ${m?.name} · ${who}${cfg.provider ? ' · ' + meta.providers[cfg.provider] : ''}`)),
    h('div', { class: 'grid2' },
      h('label', { class: 'field' }, h('span', null, 'Angle'),
        h('select', { 'data-key': 'angle', onchange: (e) => change((c) => { if (e.target.value) c.angle = e.target.value; else delete c.angle; }) },
          h('option', { value: '' }, `Seed picks${angle && angle.picked === 'seed' ? ` (now: ${label(angle.id)})` : ''}`),
          Object.keys(t.angles).map((a) => h('option', { value: a, selected: cfg.angle === a, disabled: angle && !angle.available.includes(a) }, label(a) + (angle && !angle.available.includes(a) ? ' (not allowed here)' : ''))))),
      h('label', { class: 'field' }, h('span', null, 'Language'),
        h('select', { 'data-key': 'locale', onchange: (e) => change((c) => { c.locale = e.target.value; }) }, (m?.locales || []).map((l) => h('option', { value: l, selected: (cfg.locale || m.default_locale) === l }, l.toUpperCase()))))),
    h('label', { class: 'field' }, h('span', null, 'Seed', h('span', { class: 'muted' }, ' · decides every layout and order you have not pinned')),
      h('div', { class: 'row', style: { flexWrap: 'nowrap' } },
        h('input', { type: 'text', 'data-key': 'seed', value: cfg.seed || '', oninput: (e) => change((c) => { c.seed = e.target.value; }, { redraw: false }) }),
        h('button', { class: 'btn btn--sm', type: 'button', onclick: shuffle, title: 'New seed, unpin layouts and order' }, '⟳ Shuffle'))),
    h('div', { class: 'row' },
      h('label', { class: 'check' }, h('input', { type: 'checkbox', checked: !!cfg.demo, disabled: m?.demo_only, onchange: (e) => change((c) => { if (e.target.checked) c.demo = true; else delete c.demo; }) }), 'Demo site'),
      m?.demo_only && h('span', { class: 'muted' }, 'Required: this is the demo market.'))));

  out.push(linksGroup());

  if (multi()) {
    const pages = tr._site?.pages || {};
    const rendered = S.last?.pages || [];
    out.push(h('div', { class: 'group' }, h('h3', null, 'Pages'),
      h('div', { class: 'pages' }, Object.entries(t.pages).map(([pid, pd]) => {
        const info = pages[pid] || {};
        const count = rendered.filter((p) => p.base === pid).length;
        const locked = pd.rule === 'required' || /required in market|^off:/.test(info.why || '');
        const forced = cfg.pages_on?.[pid];
        return h('div', { class: 'prow' + (info.on ? '' : ' off'), 'aria-current': String(S.page === pid), tabindex: 0,
          onclick: (e) => { if (e.target.type === 'checkbox') return; selectPage(pid); }, onkeydown: (e) => { if (e.key === 'Enter') selectPage(pid); } },
          h('input', { type: 'checkbox', checked: !!info.on, disabled: locked && forced === undefined, 'data-key': 'page-' + pid, 'aria-label': `Page ${pid} on`,
            onchange: (e) => change((c) => { obj(c, 'pages_on')[pid] = e.target.checked; }) }),
          h('div', { class: 'info' }, h('span', { class: 'name' }, label(pid), count > 1 && h('span', { class: 'chip' }, `${count} pages`), pd.generate && h('span', { class: 'chip' }, `one per ${pd.generate === 'brands' ? 'brand' : 'article'}`)),
            h('span', { class: 'why' }, info.why || '')),
          h('span', { class: 'pill ' + (info.on ? 'pill--ok' : 'pill--off') }, info.on ? 'on' : 'off'));
      }))));
  }

  const tp = tracePage();
  if (tp.frame) {
    out.push(h('div', { class: 'group' }, h('h3', null, 'Frame', h('span', { class: 'muted', style: { textTransform: 'none', letterSpacing: 0 } }, multi() ? ' · shared by every page' : '')),
      h('div', { class: 'sections' }, Object.entries(tp.frame).map(([sid, f]) => sectionRow(sid, { ...f, on: true, rule: 'required', why: 'part of every page', zone: 'frame' })))));
  }
  if (tp.sections) {
    const secs = tp.sections;
    const order = tp.order?.ids || [];
    const byZone = (z) => pageSections(S.page).filter((s) => s.zone === z).map((s) => s.id);
    const middle = [...order, ...byZone('middle').filter((id) => !order.includes(id))];
    out.push(h('div', { class: 'group' }, h('h3', null, multi() ? `Sections · ${label(S.page)}` : 'Sections',
      h('span', { class: 'pill ' + (tp.order?.picked === 'pinned' ? 'pill--off' : 'pill--seed'), title: 'How the middle order was chosen' }, `order: ${tp.order?.picked || '—'}`)),
      h('div', { class: 'zone' }, 'Top'), h('div', { class: 'sections' }, byZone('top').map((sid) => sectionRow(sid, secs[sid]))),
      h('div', { class: 'zone' }, 'Middle · drag or use ↑ ↓ to reorder'), h('div', { class: 'sections' }, middle.map((sid) => sectionRow(sid, secs[sid], order))),
      h('div', { class: 'zone' }, 'Bottom'), h('div', { class: 'sections' }, byZone('bottom').map((sid) => sectionRow(sid, secs[sid])))));
  } else if (!S.last) {
    out.push(h('p', { class: 'muted' }, 'Rendering the first preview…'));
  }
  return out;
}

function linksGroup() {
  const cfg = S.cfg;
  const t = bp();
  const keys = t.compares_brands || t.articles ? [] : ['go', 'ios', 'android', 'apk', 'qr', 'game', 'provider', 'review_form'].filter((k) => k === 'go' || cfg.links?.[k] !== undefined);
  const brandLinks = cfg.links?.brands;
  const urlInput = (key, val, set) => h('input', { type: 'text', 'data-key': 'link-' + key, value: val || '', oninput: (e) => change((c) => set(c, e.target.value), { redraw: false }) });
  const optional = ['ios', 'android', 'apk', 'qr', 'game', 'provider', 'review_form'].filter((k) => cfg.links?.[k] === undefined);
  return h('div', { class: 'group' }, h('h3', null, 'Tracked links'),
    keys.map((k) => h('label', { class: 'field' }, h('span', null, { go: 'Main brand link (every CTA)', ios: 'App Store link', android: 'Google Play link', apk: 'APK download', qr: 'QR code target', game: 'Game link pattern ({game})', provider: 'Studio link pattern ({provider})', review_form: 'Review form endpoint' }[k] || k),
      urlInput(k, cfg.links?.[k], (c, v) => { obj(c, 'links')[k] = v; }))),
    brandLinks && Object.keys(brandLinks).map((b) => h('label', { class: 'field' }, h('span', null, `${store.meta.brands[b]?.name || b} link`), urlInput('b-' + b, brandLinks[b], (c, v) => { obj(c, 'links', 'brands')[b] = v; }))),
    !t.compares_brands && !t.articles && optional.length > 0 && h('div', { class: 'row' }, h('span', { class: 'muted' }, 'Add:'),
      optional.map((k) => h('button', { class: 'btn btn--sm', type: 'button', onclick: () => change((c) => { obj(c, 'links')[k] = ''; }) }, k))));
}

function sectionRow(sid, info, order) {
  if (!info) return null;
  const meta = store.meta;
  const block = meta.blocks[info.block];
  const pins = pinsFor(S.page, sid);
  const optional = info.rule === 'optional';
  const inMiddle = order && info.on && info.zone === 'middle';
  const row = h('div', { class: 'srow' + (info.on ? '' : ' off') + (S.section === sid ? ' sel' : ''), draggable: inMiddle ? 'true' : null, 'data-sid': sid },
    h('span', { class: 'handle' + (inMiddle ? '' : ' none'), 'aria-hidden': 'true' }, inMiddle ? '⋮⋮' : '·'),
    h('div', { class: 'info', tabindex: 0, role: 'button', title: 'Edit content', onclick: () => openContent(sid), onkeydown: (e) => { if (e.key === 'Enter') openContent(sid); } },
      h('span', { class: 'name' }, sid, h('span', { class: 'muted', style: { fontWeight: 400 } }, block?.title?.replace(/^SX /, '') || info.block)),
      h('span', { class: 'why', title: info.why }, info.why)),
    h('div', { class: 'ctrls' },
      inMiddle && h('button', { class: 'btn btn--sm btn--ghost', type: 'button', 'aria-label': `Move ${sid} up`, onclick: () => moveSection(sid, -1, order) }, '↑'),
      inMiddle && h('button', { class: 'btn btn--sm btn--ghost', type: 'button', 'aria-label': `Move ${sid} down`, onclick: () => moveSection(sid, 1, order) }, '↓'),
      optional && h('input', { type: 'checkbox', checked: info.on, 'data-key': 'on-' + sid, 'aria-label': `Show ${sid}`, title: 'Show this optional section', onchange: (e) => change((c) => { forceFor(S.page)[sid] = e.target.checked; }) }),
      info.on && info.allowed && h('select', { 'data-key': 'v-' + sid, 'aria-label': `Layout of ${sid}`, onchange: (e) => change(() => { if (e.target.value) pins[sid] = e.target.value; else delete pins[sid]; }) },
        h('option', { value: '' }, `seed: ${info.picked === 'seed' ? info.variant : '…'}`),
        info.allowed.map((v) => h('option', { value: v, selected: info.picked === 'pinned' && pins[sid] === v }, v)))));
  if (inMiddle) {
    row.addEventListener('dragstart', (e) => { e.dataTransfer.setData('text/plain', sid); e.dataTransfer.effectAllowed = 'move'; });
    row.addEventListener('dragover', (e) => { e.preventDefault(); row.classList.add('drag-over'); });
    row.addEventListener('dragleave', () => row.classList.remove('drag-over'));
    row.addEventListener('drop', (e) => {
      e.preventDefault(); row.classList.remove('drag-over');
      const from = e.dataTransfer.getData('text/plain');
      if (!from || from === sid) return;
      const ids = order.filter((x) => x !== from);
      ids.splice(ids.indexOf(sid), 0, from);
      applyOrder(ids);
    });
  }
  return row;
}

function moveSection(sid, d, order) {
  const ids = [...order];
  const i = ids.indexOf(sid), j = i + d;
  if (j < 0 || j >= ids.length) return;
  [ids[i], ids[j]] = [ids[j], ids[i]];
  applyOrder(ids);
}

function applyOrder(ids) {
  const rules = tracePage().order?.rules || [];
  for (const r of rules) {
    if (r.rule === 'before' && ids.includes(r.a) && ids.includes(r.b) && ids.indexOf(r.a) > ids.indexOf(r.b)) { toast(`Order rule: "${r.a}" must come before "${r.b}".`); return; }
    if (r.rule === 'not_first' && ids[0] === r.a) { toast(`Order rule: "${r.a}" cannot be first.`); return; }
  }
  change(() => setOrder(S.page, ids));
}

function shuffle() {
  const before = JSON.stringify(S.cfg);
  change((c) => {
    const who = c.brand || c.publisher || 'site';
    c.seed = `${who}-${c.market}-${Math.random().toString(36).slice(2, 7)}`;
    if (multi()) { for (const k of Object.keys(c.variants || {})) if (typeof c.variants[k] === 'object') delete c.variants[k]; delete c.order; }
    else { for (const k of Object.keys(c.variants || {})) if (!FRAME.includes(k)) delete c.variants[k]; delete c.order; }
  });
  toast('Shuffled: new seed, layouts and order re-picked.', { action: 'Undo', onAction: () => { S.history.push(JSON.stringify(S.cfg)); S.cfg = JSON.parse(before); markDirty(); scheduleRender(0); drawTab(); } });
}

function selectPage(pid) {
  S.page = pid;
  S.section = null;
  const p = S.last?.pages?.find((x) => x.base === pid);
  if (p) { S.previewPage = p.id; scheduleRender(0); }
  drawTab();
}

function openContent(sid) { S.section = sid; S.tab = 'content'; drawTabs(); drawTab(); }

// ---------- Content ----------
function viewContent() {
  const meta = store.meta;
  const tp = tracePage();
  const ids = [...Object.keys(tp.frame || {}).filter((x) => ['header', 'footer'].includes(x)), ...pageSections(S.page).map((s) => s.id)];
  if (!S.section || !ids.includes(S.section)) S.section = ids.find((x) => tp.sections?.[x]?.on && !isFrame(x)) || ids[0];
  const sid = S.section;
  const secDef = pageSections(S.page).find((s) => s.id === sid);
  const blockName = secDef?.block || tp.frame?.[sid]?.block || sid;
  const block = meta.blocks[blockName];
  const pm = metaFor(S.page);
  const metaField = (k, max, labelText) => {
    const cnt = h('span', { class: 'count' + ((pm[k] || '').length > max ? ' over' : '') }, `${(pm[k] || '').length}/${max}`);
    return h('label', { class: 'field' }, h('span', null, labelText, cnt),
      h(k === 'title' ? 'input' : 'textarea', { id: 'meta-' + k, type: k === 'title' ? 'text' : null, rows: 2, value: pm[k] || '',
        oninput: (e) => { cnt.textContent = `${e.target.value.length}/${max}`; cnt.classList.toggle('over', e.target.value.length > max); change(() => { metaFor(S.page)[k] = e.target.value; }, { redraw: false }); } }));
  };
  const generated = multi() && bp().pages[S.page]?.generate;
  const fixed = secDef?.props && Object.keys(secDef.props).length ? secDef.props : null;
  return [
    h('div', { class: 'group' }, h('h3', null, multi() ? `Page · ${label(S.page)}` : 'Page'),
      metaField('title', 65, 'Title (search results and browser tab)'), metaField('description', 160, 'Meta description'),
      generated && h('small', { class: 'muted' }, `This page is generated once per ${generated === 'brands' ? 'brand' : 'article'}; {item} in any text becomes its name.`)),
    h('div', { class: 'group' }, h('h3', null, 'Section'),
      h('select', { onchange: (e) => { S.section = e.target.value; drawTab(); }, 'aria-label': 'Section to edit' },
        ids.map((x) => h('option', { value: x, selected: x === sid }, `${x}${isFrame(x) ? ' (frame)' : tp.sections?.[x] && !tp.sections[x].on ? ' (off)' : ''}`)))),
    block ? h('div', { class: 'group' },
      h('h3', null, block.title.replace(/^SX /, '')),
      h('p', { class: 'muted' }, block.description),
      block.guardrails.length > 0 && h('ul', { class: 'muted', style: { margin: 0, paddingLeft: '18px', fontSize: '12px' } }, block.guardrails.map((g) => h('li', null, g))),
      fixed && h('p', { class: 'muted', style: { fontSize: '12px' } }, 'Set by the blueprint: ', h('span', { class: 'mono' }, JSON.stringify(fixed))),
      fieldForm(block.fields, contentFor(S.page, sid), (val) => change(() => setContent(S.page, sid, val), { redraw: false }), `c-${sid}`))
      : h('p', { class: 'muted' }, 'This block has no editable fields.'),
  ];
}

// ---------- Style ----------
function viewStyle() {
  const meta = store.meta;
  const cfg = S.cfg;
  const p = meta.presets[cfg.preset];
  const tok = cfg.tokens || {};
  const used = meta.sites.filter((s) => s.project === S.path.split('/')[1] && s.type === cfg.type && s.path !== S.path).map((s) => s.preset);
  const fonts = [...new Set(Object.values(meta.presets).flatMap((x) => [x.fonts.display, x.fonts.body]))].sort();
  const setTok = (path, val) => change((c) => {
    const keys = path.split('.');
    const leaf = keys.pop();
    const o = obj(c, 'tokens', ...keys);
    if (val === null || val === '') delete o[leaf]; else o[leaf] = val;
    // drop empty branches so the config stays clean
    const prune = (n) => { for (const k of Object.keys(n)) { if (n[k] && typeof n[k] === 'object') { prune(n[k]); if (!Object.keys(n[k]).length) delete n[k]; } } };
    prune(c.tokens); if (!Object.keys(c.tokens).length) delete c.tokens;
  });
  const colorRow = (k) => {
    const set = tok.color?.[k];
    const val = set || p.color[k];
    const hex = /^#[0-9a-f]{6}$/i.test(val) ? val : '#000000';
    return h('div', { class: 'token' },
      h('input', { type: 'color', value: hex, 'data-key': 'c-' + k, 'aria-label': k, onchange: (e) => setTok('color.' + k, e.target.value) }),
      h('span', { class: 'tk' + (set ? ' set' : '') }, k),
      h('input', { type: 'text', class: 'mono', value: val, 'aria-label': k + ' value', onchange: (e) => setTok('color.' + k, e.target.value.trim()) }),
      set ? h('button', { class: 'btn btn--sm btn--ghost', type: 'button', onclick: () => setTok('color.' + k, null), title: 'Back to the preset value' }, 'reset') : h('span'));
  };
  const c = S.last?.contrast || [];
  return [
    h('div', { class: 'group' }, h('h3', null, 'Style preset'),
      h('div', { class: 'presets' }, Object.values(meta.presets).map((x) => h('button', { class: 'preset', type: 'button', 'aria-pressed': String(x.id === cfg.preset), onclick: () => change((cc) => { cc.preset = x.id; }) },
        h('b', null, x.name), h('span', { class: 'swatches' }, ['bg', 'surface', 'accent', 'text'].map((k) => h('i', { style: { background: x.color[k] } }))),
        h('small', null, `${x.fonts.display} · ${x.theme}`), used.includes(x.id) && h('small', { class: 'why', style: { color: 'var(--warn)' } }, 'used by a sibling site'))))),
    h('div', { class: 'group' }, h('h3', null, 'Colours', tok.color && h('button', { class: 'btn btn--sm', type: 'button', onclick: () => setTok('color', null) }, 'Reset colours')),
      Object.keys(p.color).map(colorRow)),
    h('div', { class: 'group' }, h('h3', null, 'Contrast (WCAG)'),
      c.length ? h('table', { class: 'xt' }, h('tbody', null, c.map((x) => h('tr', null, h('td', null, x.pair), h('td', { class: 'mono' }, `${x.ratio}:1`),
        h('td', null, h('span', { class: 'pill ' + (x.pass ? 'pill--ok' : 'pill--bad') }, x.pass ? `passes ${x.min}` : `below ${x.min}`)))))) : h('p', { class: 'muted' }, 'Shown after the preview renders.')),
    h('div', { class: 'group' }, h('h3', null, 'Type and shape'),
      h('div', { class: 'grid2' },
        h('label', { class: 'field' }, h('span', null, 'Display font'), h('select', { onchange: (e) => setTok('fonts.display.family', e.target.value === p.fonts.display ? null : e.target.value) }, fonts.map((f) => h('option', { value: f, selected: (tok.fonts?.display?.family || p.fonts.display) === f }, f)))),
        h('label', { class: 'field' }, h('span', null, 'Body font'), h('select', { onchange: (e) => setTok('fonts.body.family', e.target.value === p.fonts.body ? null : e.target.value) }, fonts.map((f) => h('option', { value: f, selected: (tok.fonts?.body?.family || p.fonts.body) === f }, f)))),
        h('label', { class: 'field' }, h('span', null, 'Corner radius'), h('input', { type: 'text', value: tok.shape?.radius || p.shape.radius || '', onchange: (e) => setTok('shape.radius', e.target.value.trim()) })),
        h('label', { class: 'field' }, h('span', null, 'Button radius'), h('input', { type: 'text', value: tok.shape?.buttonRadius || p.shape.buttonRadius || '', onchange: (e) => setTok('shape.buttonRadius', e.target.value.trim()) }))),
      h('label', { class: 'field' }, h('span', null, 'Spacing density', h('span', { class: 'count' }, String(tok.space?.density ?? p.density))),
        h('input', { type: 'range', min: '0.8', max: '1.3', step: '0.05', value: tok.space?.density ?? p.density, onchange: (e) => setTok('space.density', Number(e.target.value)) })),
      cfg.tokens && h('button', { class: 'btn btn--sm', type: 'button', onclick: () => change((cc) => { delete cc.tokens; }) }, 'Reset all overrides')),
  ];
}

// ---------- X-ray ----------
function viewXray() {
  const r = S.last;
  if (!r) return h('p', { class: 'muted' }, 'Rendering…');
  const out = [];
  if (r.ok === false) out.push(h('ul', { class: 'warnlist' }, h('li', { class: 'err' }, h('span', { class: 'pill pill--bad' }, 'error'), h('span', null, r.error), h('span'))));
  const angle = r.trace?._site?.angle;
  const m = r.market;
  out.push(h('div', { class: 'group' }, h('h3', null, 'Overview'), h('table', { class: 'xt' }, h('tbody', null,
    h('tr', null, h('th', null, 'Type'), h('td', null, bp().title)),
    angle && h('tr', null, h('th', null, 'Angle'), h('td', null, label(angle.id), ' ', h('span', { class: 'pill ' + (angle.picked === 'seed' ? 'pill--seed' : 'pill--off') }, angle.picked), h('div', { class: 'muted' }, `allowed here: ${angle.available.map(label).join(', ')}`))),
    r.prefix && h('tr', null, h('th', null, 'Class prefix'), h('td', { class: 'mono' }, r.prefix)),
    r.canonical && h('tr', null, h('th', null, 'Canonical'), h('td', { class: 'mono' }, r.canonical)),
    r.pages && h('tr', null, h('th', null, 'Pages'), h('td', null, `${r.pages.length} · largest ${Math.max(...r.pages.map((p) => p.kb))} KB`))))));
  if (m) {
    out.push(h('div', { class: 'group' }, h('h3', null, `Market rules · ${m.name}`), h('div', { class: 'row' },
      h('span', { class: 'chip' }, `Age ${m.min_age}+`),
      h('span', { class: 'chip' }, `Compliance strip: ${m.strip}${m.taglines ? ` (${m.taglines} mandated taglines)` : ''}`),
      h('span', { class: 'chip' }, `Allowed: ${Object.entries(m.verticals).filter(([, v]) => v).map(([k]) => k).join(', ') || 'none'}`),
      h('span', { class: 'chip' }, `Promotions cleared: ${m.promotions_allowed.length ? m.promotions_allowed.length + ' types' : 'none'}`),
      m.domain_suffix && h('span', { class: 'chip' }, `Brand links must use ${m.domain_suffix}`),
      h('span', { class: 'chip' }, `Always on: ${m.required_blocks.join(', ')}`),
      m.demo_only && h('span', { class: 'pill pill--warn' }, 'demo market'))));
  }
  const warns = [...(r.warnings || []).map((w) => ({ ...w, kind: 'warning' })), ...(r.notices || []).map((n) => ({ text: n, kind: 'notice' }))];
  out.push(h('div', { class: 'group' }, h('h3', null, `Guardrails · ${warns.length ? warns.length + ' to fix' : 'all clear'}`),
    warns.length ? h('ul', { class: 'warnlist' }, warns.map((w) => h('li', null, h('span', { class: 'pill pill--warn' }, w.page && multi() ? w.page : w.kind), h('span', null, w.text), goTo(w.text)))) : h('p', { class: 'muted' }, 'No warnings: this page set passes every build check.')));

  if (multi() && r.trace?._site?.pages) {
    out.push(h('div', { class: 'group' }, h('h3', null, 'Page set'), h('table', { class: 'xt' },
      h('thead', null, h('tr', null, h('th', null, 'Page'), h('th', null, ''), h('th', null, 'Why'))),
      h('tbody', null, Object.entries(r.trace._site.pages).map(([pid, p]) => h('tr', null, h('td', null, label(pid)), h('td', null, h('span', { class: 'pill ' + (p.on ? 'pill--ok' : 'pill--off') }, p.on ? 'on' : 'off')), h('td', { class: 'muted' }, p.why)))))));
  }
  const tp = tracePage();
  if (tp.sections) {
    out.push(h('div', { class: 'group' }, h('h3', null, multi() ? `Sections · ${label(S.page)}` : 'Sections'), h('table', { class: 'xt' },
      h('thead', null, h('tr', null, h('th', null, 'Section'), h('th', null, 'Rule and reason'), h('th', null, 'Layout'))),
      h('tbody', null, [...Object.entries(tp.frame || {}).map(([k, f]) => [k, { ...f, rule: 'frame', on: true, why: 'every page' }]), ...Object.entries(tp.sections)].map(([sid, s]) => h('tr', null,
        h('td', null, h('b', null, sid), h('div', { class: 'muted' }, s.block)),
        h('td', null, h('span', { class: 'pill ' + (s.on ? 'pill--ok' : 'pill--off') }, s.rule), ' ', h('span', { class: 'muted' }, s.why)),
        h('td', null, s.on && s.variant ? [h('span', { class: 'mono' }, s.variant), ' ', h('span', { class: 'pill ' + (s.picked === 'seed' ? 'pill--seed' : 'pill--off') }, s.picked), h('div', { class: 'muted' }, `of ${s.allowed?.join(', ')}`)] : '—')))))),
    tp.order && h('p', { class: 'muted' }, `Middle order: ${tp.order.ids.join(' → ') || '—'} (${tp.order.picked}). Rules: ${tp.order.rules.map((x) => (x.rule === 'before' ? `${x.a} before ${x.b}` : `${x.a} not first`)).join('; ') || 'none'}.`));
  }
  const wt = r.weights || {};
  if (wt.blocks) {
    const budget = store.meta.budget_kb * 1024;
    const total = Math.round((r.kb || 0) * 1024);
    const pct = Math.min(100, Math.round((100 * total) / budget));
    const max = Math.max(1, ...wt.blocks.map((b) => b.bytes), ...Object.values(wt.css_blocks || {}), wt.css_base || 0);
    const bar = (name, bytes) => h('div', { class: 'bar' }, h('span', { class: 'mono', title: name }, name), h('i', { style: { width: (100 * bytes) / max + '%' } }), h('span', { class: 'n' }, (bytes / 1024).toFixed(1) + ' KB'));
    out.push(h('div', { class: 'group' }, h('h3', null, `Weight · ${(total / 1024).toFixed(1)} of ${store.meta.budget_kb} KB`),
      h('div', { class: 'budget' + (pct > 100 ? ' bad' : pct > 90 ? ' warn' : '') }, h('i', { style: { width: pct + '%' } })),
      h('p', { class: 'muted' }, `HTML ${(wt.blocks.reduce((a, b) => a + b.bytes, 0) / 1024).toFixed(1)} KB · CSS ${(wt.css / 1024).toFixed(1)} KB (only the blocks and layouts on this page) · icons ${(wt.sprite / 1024).toFixed(1)} KB`),
      h('h4', { class: 'muted' }, 'HTML per block'), h('div', { class: 'bars' }, wt.blocks.map((b) => bar(`${b.block}${b.variant ? ':' + b.variant : ''}`, b.bytes))),
      wt.css_blocks && [h('h4', { class: 'muted' }, 'CSS per block'), h('div', { class: 'bars' }, bar('base', wt.css_base), Object.entries(wt.css_blocks).sort((a, b) => b[1] - a[1]).map(([n, b]) => bar(n, b)))]));
  }
  if (r.jsonld?.length || r.assets) {
    out.push(h('div', { class: 'group' }, h('h3', null, 'Structured data and files'),
      r.jsonld?.length ? h('pre', { class: 'code' }, JSON.stringify(r.jsonld, null, 2)) : h('p', { class: 'muted' }, 'No block-level JSON-LD on this page (the WebPage object is always added).'),
      r.assets?.['sitemap.xml'] && h('details', null, h('summary', null, 'sitemap.xml'), h('pre', { class: 'code' }, r.assets['sitemap.xml'])),
      r.assets?.['robots.txt'] && h('details', null, h('summary', null, 'robots.txt'), h('pre', { class: 'code' }, r.assets['robots.txt']))));
  }
  if (r.source) out.push(h('details', { class: 'group' }, h('summary', null, `Generated HTML of this page (${r.kb} KB)`), h('pre', { class: 'code' }, r.source)));
  return out;
}

/** "Go to" for warnings that name a field (content.<page>.<section>.<field>, meta.<page>.title). */
function goTo(text) {
  const mc = text.match(/content\.([a-z0-9_-]+)\.([a-z0-9_-]+)(?:\.([a-z0-9_.[\]-]+))?/i);
  const mm = text.match(/meta\.([a-z0-9_-]+)\.(title|description)/);
  if (!mc && !mm) return h('span');
  return h('button', { class: 'btn btn--sm', type: 'button', onclick: () => {
    if (mm) { if (multi()) S.page = mm[1]; S.tab = 'content'; drawTabs(); drawTab(); document.getElementById('meta-' + mm[2])?.focus(); return; }
    let [, a, b, rest] = mc;
    let page = S.page, sid = a, fieldPath = [b, rest].filter(Boolean).join('.');
    if (multi() && bp().pages[a]) { page = a; sid = b; fieldPath = rest || ''; }
    S.page = page; S.section = sid; S.tab = 'content'; drawTabs(); drawTab();
    const id = `c-${sid}-` + fieldPath.replace(/\[(\d+)\]/g, '-$1').replace(/\./g, '-');
    const el = document.getElementById(id) || document.querySelector(`[id^="c-${sid}-${fieldPath.split(/[.[]/)[0]}"]`);
    el?.scrollIntoView({ block: 'center' }); el?.focus();
  } }, 'Go to');
}

// ---------- JSON ----------
function viewJson() {
  const ta = h('textarea', { class: 'code', rows: 30, spellcheck: 'false', 'aria-label': 'Site config JSON', value: JSON.stringify(S.cfg, null, 2) });
  const msg = h('span', { class: 'muted' });
  return [h('p', { class: 'muted' }, 'The exact file saved to ' + S.path + '. Edit and apply; the preview and every tab follow.'), ta,
    h('div', { class: 'row' }, h('button', { class: 'btn btn--primary btn--sm', type: 'button', onclick: () => {
      try { const next = JSON.parse(ta.value); change(() => { for (const k of Object.keys(S.cfg)) delete S.cfg[k]; Object.assign(S.cfg, next); }, { redraw: false }); msg.textContent = 'Applied.'; }
      catch (e) { msg.textContent = 'Not valid JSON: ' + e.message; }
    } }, 'Apply'), msg)];
}

// ---------- diversity suggestions ----------
function diversityDialog(d, mine) {
  const box = h('div', { class: 'group' }, h('p', { class: 'muted' }, 'Looking for the single changes that make this site most different from its siblings…'));
  const dlg = modal('Diversity in this project', [
    h('p', { class: 'muted' }, 'Two sites of the same type must differ in style, angle and home hero. Overlap is the share of sections using the same layout.'),
    diversityTable({ ...d, pairs: mine.length ? mine : d.pairs }), h('h3', null, 'Suggestions'), box]);
  if (!mine.length) { box.replaceChildren(h('p', { class: 'muted' }, 'No other site of this type in the project, so nothing to compare.')); return; }
  api.post('suggest', { path: S.path, config: S.cfg }).then((r) => {
    if (!S) return;
    if (!r.ok) { box.replaceChildren(h('p', { class: 'muted' }, r.error)); return; }
    if (!r.suggestions.length) { box.replaceChildren(h('p', { class: 'muted' }, `Tried ${r.tried} single changes; none lowers the overlap further. Try two changes, or a different preset and angle together.`)); return; }
    box.replaceChildren(
      h('p', { class: 'muted' }, `Now: ${r.current.fail ? r.current.fail + ' failing pair(s), ' : ''}max overlap ${r.current.max}%. Tried ${r.tried} single changes; the best:`),
      h('table', { class: 'xt' }, h('tbody', null, r.suggestions.map((sg) => h('tr', null,
        h('td', null, sg.label), h('td', { class: 'mono' }, `${sg.fail ? sg.fail + ' failing · ' : ''}max ${sg.max}%`),
        h('td', null, h('button', { class: 'btn btn--sm btn--primary', type: 'button', onclick: () => { applyPatch(sg.patch); dlg.close(); toast(`Applied: ${sg.label}. Undo with Ctrl+Z.`); } }, 'Apply')))))));
  });
}

function applyPatch(patch) {
  const merge = (a, b) => { for (const [k, v] of Object.entries(b)) { a[k] = v && typeof v === 'object' && !Array.isArray(v) && a[k] && typeof a[k] === 'object' ? merge(a[k], v) : v; } return a; };
  change((c) => merge(c, clone(patch)));
}

// ---------- export ----------
function exportDialog() {
  const link = (kind, title, text) => h('a', { class: 'choice', href: `/api/export?path=${encodeURIComponent(S.path)}&kind=${kind}`, download: '', onclick: () => { if (S.dirty) toast('Exporting the last saved version. Save first to include your changes.'); } },
    h('b', null, title), h('small', null, text));
  modal('Export ' + S.cfg.id, [
    h('p', { class: 'muted' }, 'Each export is a zip of the saved version, built with the same engine as Build.'),
    h('div', { class: 'choices', style: { gridTemplateColumns: '1fr' } },
      link('static', 'Static site', 'For hosting: folder URLs (/payments/), sitemap.xml and robots.txt. Upload to the site root.'),
      link('preview', 'Preview package', 'Plain file links (payments.html): unzip and open index.html from disk, or share for review.'),
      link('wordpress', 'WordPress package', 'ACF field groups, theme.json for this style, the site config and install steps.')),
  ]);
}
