import {validationManager} from '@/plugins/validator/manager';

export function useValidator() {
    const validate = async () => await validationManager.validateAll();
    const resetValidation = () => validationManager.resetValidation();
    const addField = (field) => validationManager.addField(field);
    const removeField = (el) => validationManager.removeField(el);

    return {
        validate,
        resetValidation,
        addField,
        removeField
    };
}
