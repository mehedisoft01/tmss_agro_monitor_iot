// src/directives/v-validate.js
import { validationManager } from './manager';
import "@/plugins/validator/rules";

 const vValidate = {
    mounted(el, binding) {
        const rules = binding.value.split('|');
        validationManager.addField({ el, rules });
        el.setAttribute('validation-activated', '1');

        el.addEventListener('input', async () => {
            await validationManager.validateField(el, rules);
        });
        el.addEventListener('change', async () => {
            await validationManager.validateField(el, rules);
        });
        el.addEventListener('keyup', async () => {
            await validationManager.validateField(el, rules);
        });
        el.addEventListener('keydown', async () => {
            await validationManager.validateField(el, rules);
        });
    },

    unmounted(el) {
        validationManager.removeField(el);
    },
};

 export {
     vValidate
 };
