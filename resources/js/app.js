import { bootAdminDrawers } from './admin/drawer';
import { bootAdminModals } from './admin/modal';
import { bootAdminFormStates } from './admin/form-state';
import { bootAdminFilterDrawers } from './admin/filter-drawer';
import { bootAdminTabs } from './admin/tabs';
import { bootCentralNavigation } from './admin/navigation';
import { bootSiteAdminNavigation } from './admin/site-navigation';
import { bootFilterDrawers } from './public/filter-drawer';
import { bootFacetForms } from './public/facet-form';

bootAdminDrawers();
bootAdminModals();
bootAdminFormStates();
bootAdminFilterDrawers();
bootAdminTabs();
bootCentralNavigation();
bootSiteAdminNavigation();
bootFilterDrawers();
bootFacetForms();
