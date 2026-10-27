import './bootstrap';
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-fw-toggle="sidebar"]');
    if (toggle) {
        document.querySelector('.fw-sidebar')?.classList.toggle('is-open');
    }

    // Generic confirm for destructive forms: <form data-confirm="Are you sure?">
    const submitter = event.target.closest('[data-confirm]');
    if (submitter && !window.confirm(submitter.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelector('.fw-sidebar.is-open')?.classList.remove('is-open');
    }
});

// Sidebar groups start expanded; remember the ones a user collapses (per browser).
const NAV_KEY = 'fw.nav.collapsed';
const readCollapsed = () => {
    try {
        return new Set(JSON.parse(localStorage.getItem(NAV_KEY) || '[]'));
    } catch {
        return new Set();
    }
};
const writeCollapsed = (set) => {
    try {
        localStorage.setItem(NAV_KEY, JSON.stringify([...set]));
    } catch {
        // Storage unavailable (private mode): the menu simply won't remember.
    }
};

const collapsedGroups = readCollapsed();
document.querySelectorAll('[data-fw-nav-group]').forEach((heading) => {
    const group = heading.dataset.fwNavGroup;
    const list = document.querySelector(heading.dataset.bsTarget);
    if (!list) return;

    if (collapsedGroups.has(group) && !heading.hasAttribute('data-fw-nav-current')) {
        list.classList.remove('show');
        heading.classList.add('collapsed');
        heading.setAttribute('aria-expanded', 'false');
    }

    list.addEventListener('hide.bs.collapse', () => { collapsedGroups.add(group); writeCollapsed(collapsedGroups); });
    list.addEventListener('show.bs.collapse', () => { collapsedGroups.delete(group); writeCollapsed(collapsedGroups); });
});

// The sidebar scrolls on its own, so bring the current page's link into view.
document.querySelector('.fw-sidebar .fw-nav-link.is-active')?.scrollIntoView({ block: 'nearest' });
