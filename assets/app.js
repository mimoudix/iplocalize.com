/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/app.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'flag-icon-css/css/flag-icons.css';
import 'preline/dist/preline.js';
import { showAlert } from './js/functions.js';
import { showLoader } from './js/functions.js';
window.showLoader = showLoader;
window.showAlert = showAlert;

//alpine.js
import Alpine from 'alpinejs';
// Make Alpine globally available (optional but common)
window.Alpine = Alpine;
Alpine.start();

// Clipboard.js
import ClipboardJS from 'clipboard';

// Init Clipboard.js
document.addEventListener('DOMContentLoaded', () => {
    const clipboard = new ClipboardJS('[data-clipboard-text]');

    clipboard.on('success', (e) => {
        const btn = e.trigger; // Button clicked
        const icon = btn.querySelector('i');

        // Swap icon
        icon.classList.remove(btn.dataset.iconDefault);
        icon.classList.add(btn.dataset.iconSuccess);

        // Restore after 2s
        setTimeout(() => {
            icon.classList.remove(btn.dataset.iconSuccess);
            icon.classList.add(btn.dataset.iconDefault);
        }, 2000);

        e.clearSelection();
    });

    clipboard.on('error', () => {
        console.error('Copy failed');
    });
});