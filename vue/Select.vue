<template>
  <div>
    <label :for="id" class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">{{ label }}</label>
    <div class="relative">
      <select
        :id="id"
        :name="name"
        v-model="valueUpdate"
        @input="updateModelValue($event.target.value)"
        :class="selectClasses"
      >
        <option :value="''" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option.id" :value="option.id">{{ option.name }}</option>
      </select>
    </div>
    <p v-if="hasError" v-for="error of errors" :key="error.$uid" class="mt-2 text-xs text-red-600 dark:text-red-500">{{ error.$message }}</p>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits(["update:modelValue"]);
const props = defineProps({
  label: { type: String, default: "" },
  modelValue: { type: String, default: "" },
  id: { type: String, default: "" },
  name: { type: String, default: "" },
  placeholder: { type: String, default: "" },
  options: { type: Array, default: () => [] },
  errors: { type: Array, default: () => [] },
});
const valueUpdate = computed(() => props.modelValue);


const hasError = computed(() => props.errors.length > 0);

const selectClasses = computed(() => [
  "bg-gray-50",
  "border",
  "border-gray-300",
  "text-gray-900",
  "text-sm",
  "rounded-lg",
  "block",
  "w-full",
  "p-2.5",
  "placeholder-gray-500",
  "dark:text-white",
  "focus:ring-primary-600",
  "focus:border-primary-600",
  "dark:bg-gray-700",
  "dark:border-gray-600",
  "dark:placeholder-gray-400",
  "dark:text-white",
  { "border-red-500": hasError.value },
]);

const updateModelValue = (val) => {
  emit("update:modelValue", val);
};
</script>

<style>
/* Add any necessary custom styles here */
</style>
