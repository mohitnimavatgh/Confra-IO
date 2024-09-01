<template>
  <div>
    <label
      v-if="label"
      :for="id"
      class="block mb-2 text-sm font-semibold text-gray-500 dark:text-white"
      >{{ label }}</label
    >
    <div class="relative">
      <div
        v-if="icon"
        class="absolute inset-y-0 flex items-center"
        :class="[pointer?'pointer-events-none':'', iconPosition == 'right' ? 'end-0 pe-4' : 'start-0 ps-3.5']"
      >
        <i :class="icon" @click="changeInputType" class="w-4 h-4 cursor-pointer text-gray-500"></i>
      </div>
      <input
        :type="type"
        :id="id"
        :name="name"
        v-model="valueUpdate"
        :class="inputClasses"
        :placeholder="placeholder"
        autocomplete="off"
        :disabled="disabled"
      />
      <Button
        v-if="size === 'large'"
        class="absolute end-2.5 bottom-0 px-4 py-2 margin-10-px"
        outline
        @click="submit"
        frontIcon="fas fa-paper-plane"
      />
    </div>
    <p
      v-if="hasError"
      v-for="error of errors"
      :key="error.$uid"
      class="mt-2 text-xs text-red-600 dark:text-red-500"
    >
      {{ error.$message }}
    </p>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits(["update:modelValue", 'changeType', 'submitChat']);
const props = defineProps({
  label: { type: String, default: "" },
  modelValue: String,
  id: { type: String, default: "" },
  name: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  type: { type: String, default: "text" },
  icon: { type: String, default: "" },
  rules: { type: String, default: "" },
  size: { type: String, default: "medium" },
  disabled: { type: Boolean, default: false },
  errors: { type: Array, default: [] },
  iconPosition: { type: String, default: 'left' },
  pointer: { type: Boolean, default: true },
});

// const valueUpdate = ref(props.modelValue);

const hasError = computed(() => props.errors.length);

const inputClasses = computed(() => [
  "bg-gray-50",
  "border",
  "border-gray-300",
  "text-gray-900",
  "text-sm",
  "rounded-lg",
  "block",
  "w-full",
  "focus:ring-primary-500",
  "focus:border-primary-500",
  "dark:bg-gray-700",
  "dark:border-gray-600",
  "dark:placeholder-gray-400",
  "dark:text-white",
  "dark:focus:ring-primary-500",
  "dark:focus:border-primary-500",
  {
    "p-2.5": props.size === "medium",
    "p-4": props.size === "large",
    "ps-10": props.icon && props.iconPosition == 'left',
    "pe-10 ps-6": props.icon && props.iconPosition == 'right',
  },
  { "border-red-500": hasError.value },
]);

const valueUpdate = computed({
  get() {
    return props.modelValue;
  },
  set(newValue) {
    emit("update:modelValue", newValue);
  },
});

const changeInputType = () => {
  emit('changeType');
}

const submit = () => {
  emit('submitChat');
}

// const updateModelValue = (val) => {
//   valueUpdate.value = val;
//   emit("update:modelValue", val);
// };
</script>

<style>
.margin-10-px {
  margin: 10px !important;
}
</style>
