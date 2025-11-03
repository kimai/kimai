/**
 * Auto-fill description field based on selected tags
 */
export default class TagAutoFill {
    constructor() {
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', () => {
            this.setupTagListener();
        });

        // Also run immediately in case DOM is already loaded
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            this.setupTagListener();
        }
    }

    setupTagListener() {
        // Find the tags input field
        const tagsInput = document.querySelector('[data-auto-fill-target="description"]');
        
        if (!tagsInput) {
            console.warn('TagAutoFill: Tags input field not found');
            return;
        }

        // Find the description field
        const descriptionField = document.querySelector('[data-description-field="true"]');
        
        if (!descriptionField) {
            console.warn('TagAutoFill: Description field not found');
            return;
        }

        console.log('TagAutoFill: Initialized successfully', {
            tagsInput: tagsInput.id || tagsInput.name,
            descriptionField: descriptionField.id || descriptionField.name
        });

        // Listen for changes on the tags field
        // TomSelect triggers 'change' event on the original select element
        tagsInput.addEventListener('change', () => {
            setTimeout(() => {
                this.handleTagChange(tagsInput, descriptionField);
            }, 50); // Small delay to ensure TomSelect has updated the select element
        });
    }

    handleTagChange(tagsInput, descriptionField) {
        // Get selected tags
        let selectedTags = [];
        
        if (tagsInput.tagName === 'SELECT') {
            // For select elements (including TomSelect)
            const selectedOptions = Array.from(tagsInput.selectedOptions);
            selectedTags = selectedOptions.map(option => option.text.trim()).filter(tag => tag);
        } else if (tagsInput.value) {
            // For text inputs (comma-separated)
            selectedTags = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
        }

        // Only auto-fill if description is empty and we have tags
        if (descriptionField.value.trim() === '' && selectedTags.length > 0) {
            // Join tags with commas
            descriptionField.value = selectedTags.join(', ');
            
            console.log('TagAutoFill: Auto-filled description with:', selectedTags.join(', '));
            
            // Trigger change event on description field
            descriptionField.dispatchEvent(new Event('change', { bubbles: true }));
            descriptionField.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}

// Auto-initialize
new TagAutoFill();
