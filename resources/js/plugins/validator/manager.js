// src/utils/ValidationManager.js
import { validate } from 'vee-validate';

export const validationManager = {
    fields: [],

    addField(field) {
        this.fields.push(field);
    },

    removeField(el) {
        this.fields = this.fields.filter(f => f.el !== el);
    },

    async validateField(el, rules) {
        if (!rules || rules.length === 0) {
            // No rules to validate, so consider valid
            el.setCustomValidity('');
            el.classList.remove("is-invalid");
            el.removeAttribute('title');
            return true;
        }

        for (const rule of rules) {
            if (!rule) continue; // skip empty rules

            const [ruleName, params] = rule.split(':');
            const paramArray = params ? params.split(',') : [];

            // Make sure ruleName exists
            if (!ruleName) continue;

            const { valid, errors } = await validate(el.value, ruleName, paramArray);

            // Reset validity before checking
            el.setCustomValidity('');
            el.classList.remove("is-invalid");
            el.title = '';

            if (!valid) {
                el.classList.add("is-invalid");
                el.setCustomValidity(errors[0]);
                el.title = errors[0];
                el.reportValidity();
                return false;
            }
        }

        // All rules passed
        el.setCustomValidity('');
        el.classList.remove("is-invalid");
        el.removeAttribute('title');
        return true;
    },

    async validateAll() {
        let isValid = true;
        for (const field of this.fields) {
            const valid = await this.validateField(field.el, field.rules);
            if (!valid) isValid = false;
        }
        return isValid;
    },

    resetValidation() {
        this.fields.forEach(field => {
            field.el.setCustomValidity('');
            field.el.classList.remove("is-invalid");
            field.el.removeAttribute('title');
            field.el.reportValidity();
        });
    },
};
