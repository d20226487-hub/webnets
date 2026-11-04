// Small DOM helpers: element builder, toasts, modal, API calls.

export function h(tag, attrs, ...kids) {
  const el = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs || {})) {
    if (v == null || v === false) continue;
    if (k === 'class') el.className = v;
    else if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2).toLowerCase(), v);
    else if (k === 'style' && typeof v === 'object') Object.assign(el.style, v);
    else if (k === 'value') el.value = v;
    else if (k === 'checked' || k === 'disabled' || k === 'selected') el[k] = !!v;
    else el.setAttribute(k, v === true ? '' : v);
  }
  for (const kid of kids.flat(Infinity)) {
    if (kid == null || kid === false) continue;
    el.append(kid instanceof Node ? kid : document.createTextNode(String(kid)));
  }
  return el;
}

export const clone = (o) => JSON.parse(JSON.stringify(o ?? null));

export function toast(msg, { action, onAction, ms = 4500 } = {}) {
  const box = document.getElementById('toasts');
  const t = h('div', { class: 'toast', role: 'status' }, h('span', null, msg),
    action && h('button', { class: 'btn btn--sm', type: 'button', onclick: () => { onAction(); t.remove(); } }, action));
  box.append(t);
  setTimeout(() => t.remove(), ms);
}

/** Modal dialog. Returns { el, close }. Escape and the backdrop close it. */
export function modal(title, body, foot) {
  const prev = document.activeElement;
  const back = h('div', { class: 'modal-back' });
  const close = () => { back.remove(); document.removeEventListener('keydown', onKey); prev?.focus?.(); };
  const onKey = (e) => { if (e.key === 'Escape') close(); };
  const dlg = h('div', { class: 'modal', role: 'dialog', 'aria-modal': 'true', 'aria-label': title },
    h('div', { class: 'modal__head' }, h('h2', null, title), h('button', { class: 'btn btn--ghost', type: 'button', onclick: close, 'aria-label': 'Close' }, '✕')),
    h('div', { class: 'modal__body' }, body),
    foot && h('div', { class: 'modal__foot' }, foot));
  back.append(dlg);
  back.addEventListener('mousedown', (e) => { if (e.target === back) close(); });
  document.addEventListener('keydown', onKey);
  document.body.append(back);
  (dlg.querySelector('input, select, textarea, button:not(.btn--ghost)') || dlg).focus();
  return { el: dlg, close };
}

export const api = {
  get: (p) => fetch('/api/' + p).then((r) => r.json()),
  post: (p, body) => fetch('/api/' + p, { method: 'POST', headers: { 'X-SX-Builder': '1', 'Content-Type': 'application/json' }, body: JSON.stringify(body) }).then((r) => r.json()),
};

export const label = (id) => String(id).replace(/[-_]/g, ' ').replace(/^\w/, (c) => c.toUpperCase());
