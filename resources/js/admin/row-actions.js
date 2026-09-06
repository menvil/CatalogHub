function elements(menu) {
    return {
        trigger: menu.querySelector('summary'),
        panel: menu.querySelector('[data-admin-row-actions-panel]'),
    };
}

function resetPanel(panel) {
    panel.style.removeProperty('position');
    panel.style.removeProperty('top');
    panel.style.removeProperty('right');
    panel.style.removeProperty('bottom');
    panel.style.removeProperty('left');
    panel.style.removeProperty('max-height');
    panel.style.removeProperty('visibility');
    delete panel.dataset.placement;
}

function closeMenu(menu, restoreFocus = false) {
    const { trigger, panel } = elements(menu);
    menu.removeAttribute('open');
    if (panel) resetPanel(panel);
    if (restoreFocus) trigger?.focus();
}

function closeOtherMenus(except = null) {
    document.querySelectorAll('[data-admin-row-actions-menu][open]').forEach((menu) => {
        if (menu !== except) closeMenu(menu);
    });
}

function positionMenu(menu) {
    const { trigger, panel } = elements(menu);
    if (! menu.open || ! trigger || ! panel) return;

    const margin = 8;
    const gap = 4;
    const viewportWidth = document.documentElement.clientWidth;
    const viewportHeight = document.documentElement.clientHeight;
    const triggerBounds = trigger.getBoundingClientRect();

    panel.style.position = 'fixed';
    panel.style.top = '0px';
    panel.style.right = 'auto';
    panel.style.bottom = 'auto';
    panel.style.left = '0px';
    panel.style.removeProperty('max-height');
    panel.style.visibility = 'hidden';

    const panelBounds = panel.getBoundingClientRect();
    const spaceBelow = Math.max(0, viewportHeight - triggerBounds.bottom - margin - gap);
    const spaceAbove = Math.max(0, triggerBounds.top - margin - gap);
    const opensAbove = spaceBelow < panelBounds.height && spaceAbove > spaceBelow;
    const availableSpace = opensAbove ? spaceAbove : spaceBelow;
    const top = opensAbove
        ? triggerBounds.top - gap - Math.min(panelBounds.height, availableSpace)
        : triggerBounds.bottom + gap;
    const left = Math.min(
        Math.max(margin, triggerBounds.right - panelBounds.width),
        Math.max(margin, viewportWidth - panelBounds.width - margin),
    );

    panel.dataset.placement = opensAbove ? 'top' : 'bottom';
    panel.style.top = `${Math.max(margin, Math.floor(top))}px`;
    panel.style.left = `${Math.floor(left)}px`;
    if (availableSpace < panelBounds.height) {
        panel.style.maxHeight = `${Math.floor(availableSpace)}px`;
    }
    panel.style.removeProperty('visibility');
}

export function bootAdminRowActions() {
    if (window.__catalogHubAdminRowActionsBooted) return;
    window.__catalogHubAdminRowActionsBooted = true;

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return;
        const menu = event.target.closest('[data-admin-row-actions-menu]');
        closeOtherMenus(menu);

        if (event.target.closest('[data-admin-row-actions-menu] > summary')) {
            window.requestAnimationFrame(() => positionMenu(menu));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (! (event.target instanceof Element)) return;
        const menu = event.target.closest('[data-admin-row-actions-menu]');
        if (! menu) return;

        const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
        const current = items.indexOf(event.target.closest('[role="menuitem"]'));

        if (event.key === 'Escape') {
            event.preventDefault();
            closeMenu(menu, true);
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (! menu.open) menu.open = true;
            closeOtherMenus(menu);
            positionMenu(menu);
            const offset = event.key === 'ArrowDown' ? 1 : -1;
            const index = current < 0 ? (offset > 0 ? 0 : items.length - 1) : (current + offset + items.length) % items.length;
            items[index]?.focus();
        }
    });

    const repositionOpenMenus = () => {
        document.querySelectorAll('[data-admin-row-actions-menu][open]').forEach(positionMenu);
    };
    window.addEventListener('resize', repositionOpenMenus);
    window.addEventListener('scroll', repositionOpenMenus, true);
}
