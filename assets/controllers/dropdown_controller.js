import { Controller } from '@hotwired/stimulus';

/*
 * Dropdown controller for user menu and other dropdowns
 */
export default class extends Controller {
    static targets = ['menu'];

    connect() {
        // Close dropdown when clicking outside
        this.closeOnClickOutside = this.closeOnClickOutside.bind(this);
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (this.hasMenuTarget) {
            this.menuTarget.classList.toggle('hidden');

            if (!this.menuTarget.classList.contains('hidden')) {
                document.addEventListener('click', this.closeOnClickOutside);
            } else {
                document.removeEventListener('click', this.closeOnClickOutside);
            }
        }
    }

    closeOnClickOutside(event) {
        if (this.hasMenuTarget && !this.element.contains(event.target)) {
            this.menuTarget.classList.add('hidden');
            document.removeEventListener('click', this.closeOnClickOutside);
        }
    }

    disconnect() {
        document.removeEventListener('click', this.closeOnClickOutside);
    }
}
