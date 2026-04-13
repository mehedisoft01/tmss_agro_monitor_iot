<script setup>
    import { ref, onMounted, toRefs, watch } from 'vue';
    import 'jquery-ui/ui/widgets/datepicker';
    import 'jquery-ui/themes/base/all.css';


    const props = defineProps({
        name: String,
        value: {
            type: [String, Date],
            default: '',
        },
        icon: String,
        input_class: {
            type: String,
            default: 'form-control',
        },
        view_mode: {
            type: String,
            default: 'years',
        },
        id: {
            type: [String, Boolean, Number],
            default: false,
        },
        readonly: {
            type: [String, Boolean, Number],
            default: false,
        },
        editable: {
            type: Boolean,
            default: false,
        },
        placeholder: {
            type: String,
            default: 'Select Date',
        },
        format: {
            type: String,
            default: 'yy-mm',
        },
        validate: { type: [String, Object], default: '' },
        validation_name: String,
        vTooltipRight: Function,
        dataVvAs: String,
        disabled: {
            type: Boolean,
            default: false,
        },
        modelValue: {
            type: [String, Date],
            default: '',
        },
    });

    const emit = defineEmits(['update:value']);

    const inputId = ref('');
    const inputValue = ref('');

    const makeId = (length = 5) => {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    };

    const getId = () => {
        return props.id || makeId();
    };

    const dateInputed = (event) => {
        emit('update:value', event.target.value);
        emit('input', event.target.value);
    };

    onMounted(() => {
        inputId.value = getId();

        $(function () {
            $("#" + inputId.value).datepicker({
                autoclose: true,
                todayHighlight: true,
                changeMonth: true,
                changeYear: true,
                dateFormat: props.format,
                yearRange: "-100:+15",
                showOtherMonths: true,
                selectOtherMonths: true,
                beforeShow: function (input, inst) {
                    $(document).off('focusin.bs.modal');
                },
                onClose: function () {
                    $(document).on('focusin.bs.modal');
                },
                onSelect: function (dateText) {
                    emit('update:value', dateText);
                }
            });
        });
        if (props.modelValue) {
            emit('update:value', props.modelValue);
        }
    });
</script>

<template>
    <input type="text" autocomplete="off" @change="dateInputed" @keydown="dateInputed" @keyup="dateInputed" @input="dateInputed" :readonly="readonly" :id="inputId" :name="props.name" :data-vv-as="props.validation_name" :placeholder="props.placeholder" v-validate="validate" :value="props.value" :disabled="props.disabled" :class="props.input_class"/>
</template>

<style scoped>
    /* your styles here */
</style>