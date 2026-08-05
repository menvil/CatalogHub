import { bootResponsiveAdminNavigation } from './responsive-navigation';

export function bootCentralNavigation() {
    bootResponsiveAdminNavigation({
        shellSelector: '[data-central-shell]',
        sidebarSelector: '[data-central-sidebar]',
        openSelector: '[data-central-sidebar-open]',
        closeSelector: '[data-central-sidebar-close]',
        backdropSelector: '[data-central-sidebar-backdrop]',
        collapseSelector: '[data-central-sidebar-collapse]',
        collapsedDataset: 'centralSidebarCollapsed',
        mobileOpenDataset: 'centralSidebarMobileOpen',
        persistDataset: 'centralSidebarPersist',
        previewDataset: 'centralPreviewState',
        preferenceKey: 'cataloghub.central.sidebar.collapsed',
        bodyLockClass: 'central-sidebar-scroll-locked',
    });
}
