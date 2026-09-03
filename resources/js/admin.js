/**
 * SEWA HOSPITALITY — shared Alpine boot (admin bundle).
 *
 * Same shared boot as resources/js/app.js: one Alpine instance, two Vite
 * bundles. The admin layout mounts x-data on <body> (mobile drawer) and
 * Livewire 4 provides its runtime via the @livewireScripts directive.
 *
 * Plugins: focus (⌘K palette focus trap) — collapse is not needed here
 * but kept symmetrical with the public bundle so islands never depend
 * on which bundle rendered them.
 */
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.start();
