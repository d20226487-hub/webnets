// Projects: every site config under sites/<project>/, grouped by project, with a diversity matrix per project.
import { api, h, label } from './dom.js';
import { store, reloadMeta } from './main.js';
import { openWizard } from './wizard.js';

export async function renderProjects(app) {
  const meta = await reloadMeta();
  const byProject = {};
  for (const s of meta.sites) (byProject[s.project] ||= []).push(s);

  const page = h('div', { class: 'page' },
    h('div', { class: 'page-head' },
      h('div', null, h('h1', null, 'Projects'),
        h('p', { class: 'muted' }, `${meta.sites.length} sites in ${Object.keys(byProject).length} projects · ${Object.keys(meta.types).length} site types · ${Object.keys(meta.presets).length} styles`)),
      h('button', { class: 'btn btn--primary', type: 'button', onclick: () => openWizard() }, '+ New site')),
    Object.entries(byProject).map(([project, sites]) => projectSection(project, sites)));
  app.replaceChildren(page);
}

function projectSection(project, sites) {
  const matrix = h('div', { hidden: true });
  const btn = h('button', { class: 'btn btn--sm', type: 'button' }, 'Check diversity');
  btn.onclick = async () => {
    btn.disabled = true; btn.textContent = 'Checking…';
    const d = await api.post('diversity', { project });
    btn.disabled = false; btn.textContent = 'Check again';
    matrix.hidden = false;
    matrix.replaceChildren(diversityTable(d));
  };
  return h('section', { class: 'project' },
    h('div', { class: 'project-head' }, h('h2', null, project), h('span', { class: 'chip' }, `${sites.length} sites`), btn,
      h('button', { class: 'btn btn--sm', type: 'button', onclick: () => openWizard({ project }) }, '+ Add site')),
    matrix,
    h('div', { class: 'cards' }, sites.sort((a, b) => a.type.localeCompare(b.type) || a.id.localeCompare(b.id)).map(siteCard)));
}

function siteCard(s) {
  const meta = store.meta;
  const p = meta.presets[s.preset];
  const who = s.publisher ? meta.publishers[s.publisher]?.name : meta.brands[s.brand]?.name;
  const thumb = s.shot
    ? h('img', { src: s.shot, alt: '', loading: 'lazy' })
    : h('div', { class: 'card__swatch', 'aria-hidden': 'true' }, ['bg', 'accent', 'text'].map((k) => h('i', { style: { background: p?.color?.[k] || '#ccc' } })));
  return h('a', { class: 'card', href: '#/edit/' + encodeURIComponent(s.path) },
    h('div', { class: 'card__thumb' }, thumb),
    h('div', { class: 'card__body' },
      h('span', { class: 'card__title' }, s.id),
      h('span', { class: 'muted' }, `${meta.types[s.type]?.title || s.type}`),
      h('div', { class: 'card__meta' },
        h('span', { class: 'chip' }, meta.markets[s.market]?.name || s.market),
        who && h('span', { class: 'chip' }, who),
        h('span', { class: 'chip' }, p?.name || s.preset),
        s.angle && h('span', { class: 'chip' }, label(s.angle)),
        s.demo && h('span', { class: 'pill pill--warn' }, 'demo')),
      h('span', { class: 'card__foot' }, s.built ? `Built ${s.built}` : 'Not built yet', ` · edited ${s.modified}`)));
}

export function diversityTable(d) {
  if (!d.ok) return h('p', { class: 'muted' }, d.error || 'Could not check.');
  const errors = Object.entries(d.sites).filter(([, s]) => s.error);
  if (!d.pairs.length) {
    return h('p', { class: 'muted' }, 'No two sites of the same type in this project, so there is nothing to compare yet.',
      errors.length ? ` ${errors.length} site(s) could not be resolved.` : '');
  }
  return h('div', { class: 'matrix-wrap' }, h('table', { class: 'matrix' },
    h('thead', null, h('tr', null, h('th', null, 'Site'), h('th', null, 'Compared with'), h('th', null, 'Overlap'), h('th', null, 'Rule'), h('th', null, 'Same'))),
    h('tbody', null, d.pairs.sort((a, b) => b.overlap - a.overlap).map((p) => h('tr', null,
      h('td', null, p.a_id), h('td', null, p.b_id),
      h('td', { class: 'num' }, h('span', { class: 'pill ' + (p.overlap > 60 ? 'pill--warn' : 'pill--ok') }, p.overlap + '%')),
      h('td', null, h('span', { class: 'pill ' + (p.ok ? 'pill--ok' : 'pill--bad') }, p.ok ? 'passes' : 'fails')),
      h('td', { class: 'muted' }, p.same.join(', ') || '—'))))));
}
