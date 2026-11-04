// Forms generated from a block's fields.json, with the same limits the build checks (required, maxlength, repeater min/max).
import { h, clone } from './dom.js';

/**
 * fields: fields.json entries; value: current props object; onChange(newValue): called after every edit.
 * Returns an element. "variant" fields are skipped (variants are picked in the Structure tab).
 */
export function fieldForm(fields, value, onChange, idBase = 'f') {
  const v = clone(value || {});
  const set = (name, val) => {
    const f = fields.find((x) => x.name === name);
    if (f?.nullable && (val === '' || val == null)) v[name] = null; // explicit "unknown" (e.g. RTP not published)
    else if (val === '' || val == null || (Array.isArray(val) && !val.length && !f?.required)) delete v[name];
    else v[name] = val;
    onChange(clone(v));
  };
  return h('div', { class: 'group' }, fields.filter((f) => f.name !== 'variant').map((f) => field(f, v[f.name], (val) => set(f.name, val), `${idBase}-${f.name}`)));
}

function head(f, extra) {
  return h('span', null, f.label || f.name, f.required && h('span', { class: 'req', title: 'Required' }, '*'), extra);
}

function counter(f, val) {
  if (!f.maxlength) return null;
  const n = (val || '').length;
  return h('span', { class: 'count' + (n > f.maxlength ? ' over' : '') }, `${n}/${f.maxlength}`);
}

function field(f, val, set, id) {
  const hint = f.instructions && h('small', null, f.instructions);
  switch (f.type) {
    case 'text': case 'url': case 'textarea': case 'wysiwyg': {
      const big = f.type === 'textarea' || f.type === 'wysiwyg';
      const cnt = counter(f, val);
      const input = h(big ? 'textarea' : 'input', {
        id, type: big ? null : (f.type === 'url' ? 'url' : 'text'), class: f.type === 'wysiwyg' ? 'code' : null, rows: f.type === 'wysiwyg' ? 10 : 3,
        value: val ?? '', placeholder: typeof f.default === 'string' && f.default ? f.default : null,
        oninput: (e) => { set(e.target.value); if (cnt) { const n = e.target.value.length; cnt.textContent = `${n}/${f.maxlength}`; cnt.classList.toggle('over', n > f.maxlength); } },
      });
      return h('label', { class: 'field', for: id }, head(f, cnt), input,
        f.type === 'wysiwyg' ? h('small', null, 'HTML: h2, h3, p, ul, ol, li, strong, em, a, table, blockquote. Link other pages with href="page:payments".') : hint);
    }
    case 'select': {
      if (f.multiple) {
        const cur = Array.isArray(val) ? val : (f.default || []);
        return h('fieldset', { class: 'sub' }, h('legend', null, f.label || f.name),
          h('div', { class: 'row' }, f.choices.filter((c) => c !== 'auto').map((c) => h('label', { class: 'check' },
            h('input', { type: 'checkbox', checked: cur.includes(c), onchange: (e) => set(e.target.checked ? [...cur.filter((x) => x !== c), c] : cur.filter((x) => x !== c)) }), c))),
          hint);
      }
      return h('label', { class: 'field', for: id }, head(f),
        h('select', { id, onchange: (e) => set(e.target.value) }, f.choices.map((c) => h('option', { value: c, selected: (val ?? f.default ?? '') === c }, c === '' ? '(none)' : c))), hint);
    }
    case 'true_false':
      return h('label', { class: 'check' }, h('input', { id, type: 'checkbox', checked: val ?? f.default ?? false, onchange: (e) => set(e.target.checked) }), f.label || f.name);
    case 'link': {
      const cur = val || {};
      return h('fieldset', { class: 'sub' }, h('legend', null, f.label || f.name),
        h('div', { class: 'grid2' },
          h('input', { type: 'text', placeholder: 'Label', value: cur.label ?? '', oninput: (e) => set(e.target.value || cur.url ? { ...cur, label: e.target.value } : null), 'aria-label': 'Link label' }),
          h('input', { type: 'text', placeholder: 'URL or #anchor', value: cur.url ?? '', oninput: (e) => set(e.target.value || cur.label ? { ...cur, url: e.target.value } : null), 'aria-label': 'Link URL' })));
    }
    case 'group':
      return h('fieldset', { class: 'sub' }, h('legend', null, f.label || f.name), hint,
        fieldForm(f.fields || [], val || {}, (nv) => set(Object.keys(nv).length ? nv : null), id));
    case 'repeater': return repeater(f, Array.isArray(val) ? val : [], set, id);
    case 'number':
      return h('label', { class: 'field', for: id }, head(f), h('input', { id, type: 'number', step: f.step || 'any', min: f.min, max: f.max, value: val ?? '',
        placeholder: f.nullable ? 'empty = unknown' : null, oninput: (e) => set(e.target.value === '' ? null : Number(e.target.value)) }), hint);
    case 'date':
      return h('label', { class: 'field', for: id }, head(f), h('input', { id, type: 'date', value: val ?? '', oninput: (e) => set(e.target.value) }), hint);
    case 'color': {
      const txt = h('input', { type: 'text', class: 'mono', value: val ?? '', 'aria-label': (f.label || f.name) + ' hex', oninput: (e) => { set(e.target.value); if (/^#[0-9a-f]{6}$/i.test(e.target.value)) pick.value = e.target.value; } });
      const pick = h('input', { type: 'color', value: /^#[0-9a-f]{6}$/i.test(val || '') ? val : '#000000', 'aria-label': f.label || f.name, oninput: (e) => { txt.value = e.target.value; set(e.target.value); } });
      return h('div', { class: 'field' }, head(f), h('div', { class: 'token', style: { gridTemplateColumns: '34px minmax(0,1fr)' } }, pick, txt), hint);
    }
    case 'locale': {
      // One text per language: {"en": "...", "pt": "..."}; empty languages are left out.
      const cur = val && typeof val === 'object' ? { ...val } : {};
      return h('fieldset', { class: 'sub' }, h('legend', null, f.label || f.name, f.required && h('span', { class: 'req' }, ' *')), hint,
        (f.locales || ['en', 'ru', 'kk', 'pt']).map((l) => h('label', { class: 'field' }, h('span', null, l.toUpperCase()),
          h(f.long ? 'textarea' : 'input', { type: f.long ? null : 'text', rows: 2, value: cur[l] ?? '', oninput: (e) => { if (e.target.value) cur[l] = e.target.value; else delete cur[l]; set(Object.keys(cur).length ? { ...cur } : null); } }))));
    }
    case 'strings': return stringList(f, Array.isArray(val) ? [...val] : [], set, id);
    case 'kv': return keyValues(f, val && typeof val === 'object' ? { ...val } : {}, set);
    case 'map': return mapField(f, val && typeof val === 'object' && !Array.isArray(val) ? clone(val) : {}, set, id);
    case 'json': {
      const msg = h('small', { class: 'muted' }, f.instructions || 'Edited as JSON.');
      return h('label', { class: 'field' }, head(f), h('textarea', { class: 'code', rows: 8, value: val == null ? '' : JSON.stringify(val, null, 2),
        oninput: (e) => { if (!e.target.value.trim()) { set(null); msg.textContent = 'Empty.'; return; } try { set(JSON.parse(e.target.value)); msg.textContent = 'Valid JSON.'; } catch (err) { msg.textContent = 'Not valid JSON yet: ' + err.message; } } }), msg);
    }
    default:
      return h('label', { class: 'field' }, head(f), h('input', { type: 'text', value: val ?? '', oninput: (e) => set(e.target.value) }));
  }
}

function repeater(f, items, set, id) {
  const box = h('fieldset', { class: 'sub' });
  const draw = () => {
    const n = items.length;
    const status = (f.min && n < f.min) || (f.max && n > f.max) ? 'pill pill--bad' : 'pill pill--off';
    box.replaceChildren(
      h('legend', null, f.label || f.name, ' ', h('span', { class: status }, `${n}${f.min ? ` · min ${f.min}` : ''}${f.max ? ` · max ${f.max}` : ''}`)),
      ...items.map((it, i) => h('div', { class: 'rep-item' },
        h('div', { class: 'rep-item__bar' }, h('span', null, `#${i + 1}`),
          h('button', { class: 'btn btn--sm btn--ghost', type: 'button', disabled: i === 0, 'aria-label': 'Move up', onclick: () => move(i, -1) }, '↑'),
          h('button', { class: 'btn btn--sm btn--ghost', type: 'button', disabled: i === n - 1, 'aria-label': 'Move down', onclick: () => move(i, 1) }, '↓'),
          h('button', { class: 'btn btn--sm btn--ghost btn--danger', type: 'button', 'aria-label': 'Remove item', onclick: () => { items.splice(i, 1); commit(); } }, 'Remove')),
        fieldForm(f.fields || [], it, (nv) => { items[i] = nv; set(clone(items)); }, `${id}-${i}`))),
      h('button', { class: 'btn btn--sm', type: 'button', disabled: !!(f.max && n >= f.max), onclick: () => { items.push({}); commit(); } }, '+ Add item'));
  };
  const commit = () => { set(clone(items)); draw(); };
  const move = (i, d) => { [items[i], items[i + d]] = [items[i + d], items[i]]; commit(); };
  draw();
  return box;
}

/** List of short texts (pros, cons, tags, colours). */
function stringList(f, items, set, id) {
  const box = h('fieldset', { class: 'sub' });
  const draw = () => {
    const bad = f.min && items.length < f.min;
    box.replaceChildren(h('legend', null, f.label || f.name, ' ', h('span', { class: 'pill ' + (bad ? 'pill--bad' : 'pill--off') }, `${items.length}${f.min ? ` · min ${f.min}` : ''}`)),
      ...items.map((it, i) => h('div', { class: 'row', style: { flexWrap: 'nowrap' } },
        h('input', { type: 'text', id: `${id}-${i}`, value: it, 'aria-label': `${f.label || f.name} ${i + 1}`, oninput: (e) => { items[i] = e.target.value; set([...items]); } }),
        h('button', { class: 'btn btn--sm btn--ghost btn--danger', type: 'button', 'aria-label': 'Remove', onclick: () => { items.splice(i, 1); set([...items]); draw(); } }, '✕'))),
      h('button', { class: 'btn btn--sm', type: 'button', onclick: () => { items.push(''); set([...items]); draw(); box.querySelector(`#${CSS.escape(id)}-${items.length - 1}`)?.focus(); } }, '+ Add'));
  };
  draw();
  return box;
}

/** Key → number/text pairs (e.g. withdrawal minutes per payment method). */
function keyValues(f, obj, set) {
  const box = h('fieldset', { class: 'sub' });
  const draw = () => {
    const keyIn = h('input', { type: 'text', placeholder: f.keyPlaceholder || 'key', 'aria-label': 'New key', list: f.keyChoices ? `kv-${f.name}` : null });
    box.replaceChildren(h('legend', null, f.label || f.name),
      f.keyChoices && h('datalist', { id: `kv-${f.name}` }, f.keyChoices.map((k) => h('option', { value: k }))),
      ...Object.entries(obj).map(([k, v]) => h('div', { class: 'grid2', style: { alignItems: 'center' } }, h('span', { class: 'mono' }, k),
        h('div', { class: 'row', style: { flexWrap: 'nowrap' } },
          h('input', { type: f.valueType === 'number' ? 'number' : 'text', value: v ?? '', 'aria-label': k, oninput: (e) => { obj[k] = f.valueType === 'number' ? Number(e.target.value) : e.target.value; set({ ...obj }); } }),
          h('button', { class: 'btn btn--sm btn--ghost btn--danger', type: 'button', 'aria-label': `Remove ${k}`, onclick: () => { delete obj[k]; set({ ...obj }); draw(); } }, '✕')))),
      h('div', { class: 'row', style: { flexWrap: 'nowrap' } }, keyIn, h('button', { class: 'btn btn--sm', type: 'button', onclick: () => {
        const k = keyIn.value.trim(); if (!k || k in obj) return; obj[k] = f.valueType === 'number' ? 0 : ''; set({ ...obj }); draw();
      } }, '+ Add')));
  };
  draw();
  return box;
}

/** Object keyed by id ({"br": {...}, "xx": {...}}), each value edited with f.fields. Nested maps are fine. */
function mapField(f, obj, set, id) {
  const box = h('fieldset', { class: 'sub' });
  const open = new Set();
  const draw = () => {
    const keyIn = h('input', { type: 'text', placeholder: f.keyPlaceholder || 'new id', 'aria-label': `New ${f.keyLabel || 'id'}`, list: f.keyChoices ? `map-${id}` : null });
    box.replaceChildren(h('legend', null, f.label || f.name, ' ', h('span', { class: 'pill pill--off' }, `${Object.keys(obj).length}`)),
      f.keyChoices && h('datalist', { id: `map-${id}` }, f.keyChoices.map((k) => h('option', { value: k }))),
      ...Object.keys(obj).map((k) => {
        const det = h('details', { class: 'rep-item', open: open.has(k) || null, ontoggle: (e) => (e.target.open ? open.add(k) : open.delete(k)) },
          h('summary', { class: 'rep-item__bar', style: { cursor: 'pointer' } }, h('span', null, k),
            h('button', { class: 'btn btn--sm btn--ghost btn--danger', type: 'button', onclick: (e) => { e.preventDefault(); delete obj[k]; set(clone(obj)); draw(); } }, 'Remove')),
          fieldForm(f.fields || [], obj[k], (nv) => { obj[k] = nv; set(clone(obj)); }, `${id}-${k}`));
        return det;
      }),
      h('div', { class: 'row', style: { flexWrap: 'nowrap' } }, keyIn, h('button', { class: 'btn btn--sm', type: 'button', onclick: () => {
        const k = keyIn.value.trim().toLowerCase();
        if (!/^[a-z0-9_-]+$/.test(k) || k in obj) { keyIn.setCustomValidity('Use lowercase letters, digits, dashes; must be new.'); keyIn.reportValidity(); return; }
        obj[k] = {}; open.add(k); set(clone(obj)); draw();
      } }, `+ Add ${f.keyLabel || 'entry'}`)));
  };
  draw();
  return box;
}
