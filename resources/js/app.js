import './bootstrap';
import { initNavbarToggle } from './navbar';
import { initPasswordToggle } from './auth';
import { initRegistration } from './registration';
import { initConfirmForms } from './account';
import { initProductGallery } from './gallery';
import { initFilterAutoSubmit } from './filters';
import { initFulfillmentAutoSubmit } from './orders';
import { initBulkProductActions } from './admin';
import { initNewsletterForm } from './newsletter';

document.addEventListener('DOMContentLoaded', () => {
    initNavbarToggle();
    initPasswordToggle();
    initRegistration();
    initConfirmForms();
    initProductGallery();
    initFilterAutoSubmit();
    initFulfillmentAutoSubmit();
    initBulkProductActions();
    initNewsletterForm();
});
