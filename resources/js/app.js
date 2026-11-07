import { createPopper } from '@popperjs/core';
import './bootstrap';
import ApexCharts from 'apexcharts';
import { createIcons, icons } from 'lucide';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// FullCalendar
import { Calendar } from 'fullcalendar';

import './offline-pos';

// Register Service Worker for Offline PWA
if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('[ServiceWorker] registration failed:', err);
        });
    });
}



window.createPopper = createPopper;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;
window.lucide = {
    createIcons: (opts = {}) => createIcons({ icons, ...opts }),
};

export function initDatePickers(root = document) {
    if (typeof flatpickr === 'undefined') return;
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input[type="date"]').forEach((input) => {
        if (input._flatpickr || input.hasAttribute('data-native-date')) return;
        const placeholder = input.getAttribute('placeholder') || 'dd/mm/yyyy';
        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputPlaceholder: placeholder,
            altInputClass: input.className || 'rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#465fff] focus:outline-none focus:ring-2 focus:ring-[#465fff]/15',
            allowInput: true,
            onReady: (_, __, instance) => {
                if (instance.altInput) {
                    instance.altInput.placeholder = placeholder;
                }
            },
            onChange: (_, dateStr, instance) => {
                instance.input.value = dateStr;
                instance.input.dispatchEvent(new Event('input', { bubbles: true }));
                instance.input.dispatchEvent(new Event('change', { bubbles: true }));
            },
            onClose: (_, __, instance) => {
                if (!instance.input.value && instance.altInput) {
                    instance.altInput.placeholder = placeholder;
                }
            },
        });
    });
}
window.initDatePickers = initDatePickers;

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    initDatePickers();
    document.querySelectorAll('form[method="get"]').forEach((form) => {
        if (form.closest('header')) return;
        form.classList.add('auto-filter');
        let timer;
        form.querySelectorAll('select, input[type="date"]').forEach((field) => {
            field.addEventListener('change', () => form.requestSubmit());
        });
        form.querySelectorAll('input[type="search"], input[name="search"]').forEach((field) => {
            field.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => form.requestSubmit(), 450);
            });
        });
    });
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
});

// Livewire replaces table markup during search/filter updates, so refresh icons after morph completes.
document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', () => createIcons({ icons }));
});
