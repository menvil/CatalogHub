import { bootResponsiveAdminNavigation } from './responsive-navigation';

export function bootSiteAdminNavigation() {
    const initializedShells = bootResponsiveAdminNavigation({
        shellSelector: '[data-site-shell]',
        sidebarSelector: '[data-site-sidebar]',
        openSelector: '[data-site-sidebar-open]',
        closeSelector: '[data-site-sidebar-close]',
        backdropSelector: '[data-site-sidebar-backdrop]',
        collapseSelector: '[data-site-sidebar-collapse]',
        closeOnNavigateSelector: '[data-site-selector-link]',
        collapsedDataset: 'siteSidebarCollapsed',
        mobileOpenDataset: 'siteSidebarMobileOpen',
        persistDataset: 'siteSidebarPersist',
        previewDataset: 'sitePreviewState',
        preferenceKey: 'cataloghub.site.sidebar.collapsed',
        bodyLockClass: 'site-sidebar-scroll-locked',
    });

    initializedShells.forEach((shell) => {
        shell.dataset.siteShellReady = 'true';
        shell.dispatchEvent(new CustomEvent('site-admin-shell:ready', { bubbles: true }));
    });
}
