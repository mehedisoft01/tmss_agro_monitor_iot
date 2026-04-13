<script setup>
import { ref, onMounted, watch } from 'vue';
import 'jquery-ui/ui/widgets/datepicker';

const props = defineProps({
  name: String,
  modelValue: { type: [String, Date], default: '' },
  format: { type: String, default: 'yy-mm-dd' },
  placeholder: { type: String, default: 'Select Date' },
  input_class: { type: String, default: 'form-control' },
  readonly: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  validation_name: String,
  validate: { type: [String, Object], default: '' },
});

const emit = defineEmits(['update:modelValue']);

const inputId = ref('');

const makeId = (length = 5) => {
  let result = '';
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  for (let i = 0; i < length; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
  return result;
};

inputId.value = makeId();

onMounted(() => {
  $("#" + inputId.value).datepicker({
    autoclose: true,
    todayHighlight: true,
    changeMonth: true,
    changeYear: true,
    dateFormat: props.format,
    yearRange: "-100:+15",
    showOtherMonths: true,
    selectOtherMonths: true,
    beforeShow: () => $(document).off('focusin.bs.modal'),
    onClose: () => $(document).on('focusin.bs.modal'),
    onSelect: (dateText) => {
      emit('update:modelValue', dateText);
    }
  });

  // set initial value in datepicker
  if (props.modelValue) {
    $("#" + inputId.value).datepicker('setDate', props.modelValue);
  }
});

// watch parent changes
watch(
  () => props.modelValue,
  (newVal) => {
    $("#" + inputId.value).datepicker('setDate', newVal);
  }
);
</script>

<template>
  <input
    type="text"
    :id="inputId"
    :name="name"
    :placeholder="placeholder"
    :class="input_class"
    :readonly="readonly"
    :disabled="disabled"
    v-validate="validate"
    :data-vv-as="validation_name"
  />
</template>
<style scoped>

</style>
