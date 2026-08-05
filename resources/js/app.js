import { bootAdminDrawers } from './admin/drawer';
import { bootAdminModals } from './admin/modal';
import { bootCentralNavigation } from './admin/navigation';
import { bootSiteAdminNavigation } from './admin/site-navigation';
import { bootFilterDrawers } from './public/filter-drawer';
import { bootFacetForms } from './public/facet-form';

bootAdminDrawers();
bootAdminModals();
bootCentralNavigation();
bootSiteAdminNavigation();
bootFilterDrawers();
bootFacetForms();
