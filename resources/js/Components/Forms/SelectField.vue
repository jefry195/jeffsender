<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  options: {
    type: Array,
    required: true,
    default: () => []
  },
  optionLabel: {
    type: [String, Function],
    default: 'label'
  },
  optionValue: {
    type: [String, Function],
    default: 'value'
  },
  placeholder: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const inputId = computed(() =>
  props.id || `select-${Math.random().toString(36).substr(2, 9)}`
);

const getOptionValue = (option) => {
  if (typeof props.optionValue === 'function') {
    return props.optionValue(option);
  }

  if (!option[props.optionLabel] && option.id) {
    return option.id
  }

  return typeof option === 'object' ? option[props.optionValue] : option;
};

const getOptionLabel = (option) => {
  if (typeof props.optionLabel === 'function') {
    return props.optionLabel(option);
  }

  if (!option[props.optionLabel] && option.name) {
    return option.name
  }

  return typeof option === 'object' ? option[props.optionLabel] : option;
};

const handleChange = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
  emit('change', value);
};
</script>

<template>
  <div>
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>

    <div>
      <select :id="inputId" :value="modelValue" @change="handleChange" :disabled="disabled" :required="required"
        class="select" :class="{ 'has-error': error }">
        <option v-if="placeholder" value="" disabled>
          {{ placeholder }}
        </option>

        <option v-for="option in options" :key="getOptionValue(option)" :value="getOptionValue(option)">
          {{ getOptionLabel(option) }}
        </option>
      </select>
    </div>

    <p v-if="error" class="error-message">{{ error }}</p>
    <p v-else-if="hint" class="hint-message">{{ hint }}</p>
  </div>
</template>
