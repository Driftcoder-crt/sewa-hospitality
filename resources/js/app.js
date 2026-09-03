/**
 * SEWA HOSPITALITY — shared Alpine boot (public bundle).
 *
 * Alpine drives the small interactive islands (drawers, toggles, accordions).
 * Livewire 4 injects its own runtime via the @livewireScripts Blade directive
 * at the end of <body> and reuses this Alpine instance through `alpine:init`.
 * Module scripts execute after document parse, i.e. after Livewire's classic
 * script has registered its listeners — this order is required.
 */
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(intersect);

Alpine.start();
