@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    id="modal-{{ $name }}"
    class="modal-container fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
    style="display: {{ $show ? 'block' : 'none' }};"
    data-modal-name="{{ $name }}"
    data-focusable="{{ $attributes->has('focusable') ? 'true' : 'false' }}"
>
    <div class="modal-backdrop fixed inset-0 transform transition-all">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div class="modal-content mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto">
        {{ $slot }}
    </div>
</div>

<script>
(function() {
    'use strict';

    const modalId = 'modal-{{ $name }}';
    const modal = document.getElementById(modalId);

    if (!modal) return;

    const modalName = modal.getAttribute('data-modal-name');
    const isFocusable = modal.getAttribute('data-focusable') === 'true';
    const backdrop = modal.querySelector('.modal-backdrop');

    // Helper function to get all focusable elements
    function getFocusables() {
        const selector = 'a, button, input:not([type="hidden"]), textarea, select, details, [tabindex]:not([tabindex="-1"])';
        return Array.from(modal.querySelectorAll(selector))
            .filter(el => !el.hasAttribute('disabled'));
    }

    // Show modal function
    function showModal() {
        modal.style.display = 'block';
        document.body.classList.add('overflow-y-hidden');

        if (isFocusable) {
            setTimeout(() => {
                const focusables = getFocusables();
                if (focusables.length > 0) {
                    focusables[0].focus();
                }
            }, 100);
        }
    }

    // Hide modal function
    function hideModal() {
        modal.style.display = 'none';
        document.body.classList.remove('overflow-y-hidden');
    }

    // Listen for custom open-modal event
    window.addEventListener('open-modal', function(event) {
        if (event.detail === modalName) {
            showModal();
        }
    });

    // Listen for custom close-modal event
    window.addEventListener('close-modal', function(event) {
        if (event.detail === modalName) {
            hideModal();
        }
    });

    // Listen for close event (generic)
    window.addEventListener('close', function() {
        hideModal();
    });

    // Close on backdrop click
    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop || e.target.classList.contains('bg-gray-500')) {
                hideModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            hideModal();
        }
    });

    // Tab key trap (keep focus within modal)
    modal.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const focusables = getFocusables();
            if (focusables.length === 0) return;

            const firstFocusable = focusables[0];
            const lastFocusable = focusables[focusables.length - 1];

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
    });
})();
</script>
