// Data: forms for the shared data every site reads (brands, studios, catalogs, offers, facts, ratings, reviews, articles).
// After each save every site is rebuilt in memory, so you see at once which sites a change breaks or warns about.
import { api, h, toast, modal } from './dom.js';
import { store } from './main.js';
import { fieldForm } from './forms.js';
import { collections } from './datasets.js';

let D = null;

export function leaveData() {
  if (!D) return true;
  const target = location.hash;
  if (D.dirty && !D.discard && target !== D.hash) {
    history.replaceState(null, '', D.hash); // put the address back; nothing is re-rendered
    const go = () => { D.discard = true; dlg.close(); location.hash = target; };
    const dlg = modal('Unsaved changes', h('p', null, `${D.path} has changes that are not saved yet.`), [
      h('button', { class: 'btn', type: 'button', onclick: () => dlg.close() }, 'Stay'),
      h('button', { class: 'btn btn--danger', type: 'button', onclick: go }, 'Discard changes'),
      h('button', { class: 'btn btn--primary', type: 'button', onclick: async () => { if (await save()) go(); } }, 'Save and leave')]);
    return false;
  }
  D = null;
  return true;
}

export async function renderData(app, rest) {
  const meta = store.meta;
  const colls = collections(meta);
  const list = await api.get('data/list');
  const [cid, encPath] = rest.split('/');
  const coll = colls.find((c) => c.id === cid) || colls[0];
  const files = list.files.filter((f) => coll.files(f.path)).map((f) => f.path).sort();
  const path = encPath ? decodeURIComponent(encPath) : files[0];
  D = { coll, colls, files, path, data: null, saved: '', dirty: false, item: null, filter: '', raw: false, check: null, dirs: list.content_dirs, allFiles: list.files, hash: location.hash, el: {} };
  if (path) {
    const r = await api.get('data/file?path=' + encodeURIComponent(path));
    if (r.ok) { D.data = r.data; D.saved = JSON.stringify(r.data); D.readonly = r.readonly; }
  }
  D.el.main = h('section', { class: 'dmain' });
  app.replaceChildren(h('div', { class: 'data' }, nav(), D.el.main));
  draw();
}

function nav() {
  const groups = {};
  for (const c of D.colls) (groups[c.group] ||= []).push(c);
  return h('nav', { class: 'dnav', 'aria-label': 'Data collections' }, Object.entries(groups).map(([g, cs]) => h('div', { class: 'group' }, h('h3', null, g),
    cs.map((c) => {
      const n = D.allFiles.filter((f) => c.files(f.path)).length;
      return h('a', { class: 'dnav__item', href: '#/data/' + c.id, 'aria-current': c.id === D.coll.id ? 'page' : null }, h('span', null, c.title), h('span', { class: 'chip' }, n));
    }))));
}

const rootOf = () => (D.coll.root ? (D.data[D.coll.root] ||= D.coll.shape === 'list' ? [] : {}) : D.data);
function markDirty() { D.dirty = JSON.stringify(D.data) !== D.saved; D.el.save && (D.el.save.disabled = !D.dirty || D.readonly); D.el.title?.classList.toggle('dirty', D.dirty); }

function draw() {
  const c = D.coll;
  const main = D.el.main;
  D.el.title = h('h1', null, c.title);
  D.el.save = h('button', { class: 'btn btn--primary btn--sm', type: 'button', disabled: true, onclick: save }, 'Save');
  const head = h('div', { class: 'dhead' },
    h('div', null, D.el.title, h('p', { class: 'muted' }, c.hint)),
    h('div', { class: 'row' },
      D.files.length > 1 && h('select', { 'aria-label': 'File', onchange: (e) => { location.hash = `#/data/${c.id}/${encodeURIComponent(e.target.value)}`; } },
        D.files.map((f) => h('option', { value: f, selected: f === D.path }, f))),
      D.files.length === 1 && h('span', { class: 'mono muted' }, D.path),
      c.newFile && h('button', { class: 'btn btn--sm', type: 'button', onclick: newFile }, c.slugFile ? '+ New article' : '+ New file'),
      D.data && c.shape !== 'readonly' && h('button', { class: 'btn btn--sm', type: 'button', 'aria-pressed': String(D.raw), onclick: () => { D.raw = !D.raw; draw(); } }, D.raw ? 'Form' : 'Raw JSON'),
      h('button', { class: 'btn btn--sm', type: 'button', onclick: runCheck, title: 'Build every site in memory with the current saved data' }, 'Check all sites'),
      c.shape !== 'readonly' && D.el.save));
  const parts = [head];
  if (D.check) parts.push(checkReport());
  if (!D.data) parts.push(h('p', { class: 'muted' }, c.newFile ? 'No file yet. Create one with the button above.' : 'Nothing here yet.'));
  else if (c.shape === 'readonly') parts.push(h('p', { class: 'muted' }, 'Compliance owns these rules; change them in the file with compliance sign-off.'), h('pre', { class: 'code', style: { maxHeight: 'none' } }, JSON.stringify(D.data, null, 2)));
  else if (D.raw) parts.push(rawEditor());
  else if (c.shape === 'file') parts.push(h('div', { class: 'dform' }, fieldForm(c.fields, D.data, (v) => { D.data = v; markDirty(); }, 'd')));
  else parts.push(itemsEditor());
  main.replaceChildren(...parts);
  markDirty();
}

function itemsEditor() {
  const c = D.coll;
  const root = rootOf();
  const isMap = c.shape === 'map';
  const keys = isMap ? Object.keys(root).filter((k) => k[0] !== '_') : root.map((_, i) => i);
  if (D.item == null || !(isMap ? D.item in root : D.item < root.length)) D.item = keys[0] ?? null;
  const q = D.filter.toLowerCase();
  const items = h('div', { class: 'ditems' },
    h('input', { type: 'search', placeholder: `Filter ${keys.length} items`, value: D.filter, 'aria-label': 'Filter items', oninput: (e) => {
      D.filter = e.target.value; draw();
      const s = D.el.main.querySelector('input[type=search]'); s.focus(); s.setSelectionRange(s.value.length, s.value.length);
    } }),
    h('div', { class: 'ditems__list', role: 'list' }, keys.filter((k) => !q || String(c.label(root[k], k)).toLowerCase().includes(q) || String(k).includes(q)).map((k) =>
      h('button', { class: 'ditem', type: 'button', role: 'listitem', 'aria-current': String(k === D.item), onclick: () => { D.item = k; draw(); } },
        h('span', null, c.label(root[k], k)), isMap && h('small', { class: 'mono muted' }, k)))),
    addControl(root, isMap));
  const cur = D.item != null ? root[D.item] : null;
  const confirmRemove = h('button', { class: 'btn btn--sm btn--danger', type: 'button' }, 'Remove item');
  confirmRemove.onclick = () => {
    if (confirmRemove.dataset.armed !== '1') { confirmRemove.dataset.armed = '1'; confirmRemove.textContent = 'Click again to remove'; return; }
    if (isMap) delete root[D.item]; else root.splice(D.item, 1);
    D.item = null; markDirty(); draw();
  };
  const form = cur == null ? h('p', { class: 'muted' }, 'Add an item to start.') : h('div', { class: 'dform' },
    h('div', { class: 'row' }, h('h2', null, c.label(cur, D.item)), h('span', { style: { marginLeft: 'auto' } }), confirmRemove),
    fieldForm(c.fields, cur, (v) => { root[D.item] = v; markDirty(); }, `d-${D.item}`));
  return h('div', { class: 'dsplit' }, items, form);
}

function addControl(root, isMap) {
  const c = D.coll;
  if (!isMap) return h('button', { class: 'btn btn--sm', type: 'button', onclick: () => { root.push({}); D.item = root.length - 1; markDirty(); draw(); } }, '+ Add item');
  const key = h('input', { type: 'text', placeholder: c.keyLabel || 'new id', 'aria-label': `New ${c.keyLabel || 'id'}`, list: c.keyChoices ? 'dkeys' : null });
  return h('div', { class: 'row', style: { flexWrap: 'nowrap' } }, c.keyChoices && h('datalist', { id: 'dkeys' }, c.keyChoices.filter((k) => !(k in root)).map((k) => h('option', { value: k }))), key,
    h('button', { class: 'btn btn--sm', type: 'button', onclick: () => {
      const k = key.value.trim().toLowerCase();
      if (!/^[a-z0-9_-]+$/.test(k) || k in root) { key.setCustomValidity('Lowercase letters, digits and dashes; must be new.'); key.reportValidity(); return; }
      root[k] = {}; D.item = k; markDirty(); draw();
    } }, '+ Add'));
}

function rawEditor() {
  const ta = h('textarea', { class: 'code', rows: 30, spellcheck: 'false', 'aria-label': 'File JSON', value: JSON.stringify(D.data, null, 2) });
  const msg = h('span', { class: 'muted' });
  return h('div', { class: 'dform' }, ta, h('div', { class: 'row' }, h('button', { class: 'btn btn--sm', type: 'button', onclick: () => {
    try { D.data = JSON.parse(ta.value); markDirty(); msg.textContent = 'Applied. Save to write the file.'; } catch (e) { msg.textContent = 'Not valid JSON: ' + e.message; }
  } }, 'Apply'), msg));
}

async function save() {
  if (D.readonly) return false;
  const r = await api.post('data/save', { path: D.path, data: D.data });
  if (!r.ok) { toast(r.error); return false; }
  D.saved = JSON.stringify(D.data); markDirty();
  toast(`Saved ${D.path}${r.backup ? ' · previous version kept in dist/backups/' : ''}`);
  runCheck();
  return true;
}

async function runCheck() {
  D.check = { running: true };
  draw();
  const r = await api.post('data/check', {});
  if (!D) return;
  D.check = r;
  draw();
}

function checkReport() {
  const r = D.check;
  if (r.running) return h('div', { class: 'dcheck' }, h('span', { class: 'muted' }, 'Building every site with the saved data…'));
  const broken = r.sites.filter((s) => !s.ok), warned = r.sites.filter((s) => s.ok && s.warnings.length);
  return h('div', { class: 'dcheck' },
    h('div', { class: 'row' }, h('b', null, 'All sites with this data:'),
      h('span', { class: 'pill pill--ok' }, `${r.sites.length - broken.length - warned.length} pass`),
      warned.length > 0 && h('span', { class: 'pill pill--warn' }, `${warned.length} with warnings`),
      broken.length > 0 && h('span', { class: 'pill pill--bad' }, `${broken.length} do not build`),
      h('button', { class: 'btn btn--sm btn--ghost', type: 'button', style: { marginLeft: 'auto' }, onclick: () => { D.check = null; draw(); } }, 'Hide')),
    (broken.length || warned.length) > 0 && h('ul', { class: 'warnlist' }, [...broken, ...warned].map((s) => h('li', { class: s.ok ? '' : 'err' },
      h('span', { class: 'pill ' + (s.ok ? 'pill--warn' : 'pill--bad') }, s.id), h('span', null, s.ok ? s.warnings.slice(0, 3).join(' · ') : s.error),
      h('a', { class: 'btn btn--sm', href: '#/edit/' + encodeURIComponent(s.path) }, 'Open')))));
}

function newFile() {
  const c = D.coll;
  const nf = c.newFile;
  const existing = new Set(D.files);
  let pick = '', slug = '';
  const body = h('div', { class: 'group' });
  const go = h('button', { class: 'btn btn--primary', type: 'button', disabled: true }, 'Create');
  const path = () => (nf.dirs ? (pick && slug ? nf.path(pick, slug) : '') : (pick ? nf.path(pick) : ''));
  const refresh = () => { const p = path(); go.disabled = !p || existing.has(p) || (nf.dirs && !/^[a-z0-9-]+$/.test(slug)); note.textContent = p ? (existing.has(p) ? `${p} already exists.` : `Creates ${p}`) : ''; };
  const note = h('small', { class: 'muted' });
  if (nf.dirs) {
    body.append(h('label', { class: 'field' }, h('span', null, nf.label), h('select', { onchange: (e) => { pick = e.target.value; refresh(); } }, h('option', { value: '' }, 'Choose…'), D.dirs.map((d) => h('option', { value: d }, d)))),
      h('label', { class: 'field' }, h('span', null, 'Slug (file name and URL)'), h('input', { type: 'text', placeholder: 'my-article', oninput: (e) => { slug = e.target.value.trim(); refresh(); } })));
  } else {
    body.append(h('label', { class: 'field' }, h('span', null, nf.label), h('select', { onchange: (e) => { pick = e.target.value; refresh(); } }, h('option', { value: '' }, 'Choose…'),
      nf.choices.map((k) => h('option', { value: k, disabled: existing.has(nf.path(k)) }, k + (existing.has(nf.path(k)) ? ' (exists)' : ''))))));
  }
  body.append(note);
  const dlg = modal(c.slugFile ? 'New article' : `New ${c.title.toLowerCase()} file`, body, [h('button', { class: 'btn', type: 'button', onclick: () => dlg.close() }, 'Cancel'), go]);
  go.onclick = async () => {
    const p = path();
    const r = await api.post('data/save', { path: p, data: nf.data(slug), create: true });
    if (!r.ok) { toast(r.error); return; }
    dlg.close();
    D.dirty = false;
    location.hash = `#/data/${c.id}/${encodeURIComponent(p)}`;
  };
}

