// App shell: loads metadata once and routes #/ (projects), #/new (wizard), #/edit/<path> (editor).
import { api, h } from './dom.js';
import { renderProjects } from './projects.js';
import { openWizard } from './wizard.js';
import { renderEditor, leaveEditor } from './editor.js';
import { renderData, leaveData } from './data.js';

export const store = { meta: null };

export async function reloadMeta() {
  store.meta = await api.get('meta');
  return store.meta;
}

async function route() {
  const app = document.getElementById('app');
  const hash = location.hash || '#/';
  if (!leaveEditor() || !leaveData()) return; // unsaved changes: the screen asked to stay
  document.querySelectorAll('[data-nav]').forEach((a) => a.toggleAttribute('aria-current', (hash === '#/' && a.dataset.nav === 'projects') || (hash === '#/new' && a.dataset.nav === 'new') || (hash.startsWith('#/data') && a.dataset.nav === 'data')));
  if (!store.meta) await reloadMeta();
  if (hash.startsWith('#/data')) {
    await renderData(app, hash.slice(7));
  } else if (hash.startsWith('#/edit/')) {
    await renderEditor(app, decodeURIComponent(hash.slice(7)));
  } else {
    await renderProjects(app);
    if (hash === '#/new') openWizard();
  }
}

window.addEventListener('hashchange', route);
route().catch((e) => {
  document.getElementById('app').replaceChildren(h('div', { class: 'page' }, h('h1', null, 'The builder could not start'), h('p', { class: 'muted' }, String(e))));
});
