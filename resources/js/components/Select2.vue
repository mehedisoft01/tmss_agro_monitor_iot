<template>
    <select ref="select" class="form-select">
        <option v-for="option in options" :key="option.id" :value="option.id">
            {{ option.text }}
        </option>
    </select>
</template>

<script setup>
    import { ref, watch, onMounted, defineProps, defineEmits } from 'vue';
    import jQuery from 'jquery';
    import 'select2/dist/css/select2.min.css';
    import select2 from 'select2';

    jQuery.fn.select2 = select2;

    const props = defineProps({
        modelValue: [String, Number],
        options: {
            type: Array,
            default: () => []
        },
        placeholder: {
            type: String,
            default: 'Select an option'
        }
    });

    const emit = defineEmits(['update:modelValue']);
    const select = ref(null);

    onMounted(() => {
        const $select = jQuery(select.value);

        function formatOption(option) {
            if (!option.id) return option.text;
            return jQuery(`<span>${option.text}</span>`);
        }

        $select.select2({
            placeholder: props.placeholder,
            width: '100%',
            allowClear: true,
            minimumResultsForSearch: 0,
            templateResult: formatOption,
            templateSelection: formatOption
        });

        $select.val(props.modelValue).trigger('change');

        $select.on('change', function () {
            emit('update:modelValue', jQuery(this).val());
        });
    });

    watch(() => props.modelValue, (newValue) => {
        jQuery(select.value).val(newValue).trigger('change');
    });
</script>
