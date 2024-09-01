<template>
  <div class="relative overflow-hidden sm:rounded-lg">
    <label class="block mb-2 text-md font-medium text-gray-700 dark:text-white">{{ title }}</label>
    <div class="flex items-center justify-end flex-column flex-wrap md:flex-row pb-4 bg-white dark:bg-gray-800">
      <div class="my-1 overflow-x-auto mr-2">
        <tab-button-group :items="filterTab" @tab-click="handleTabClick" />
      </div>
      <div class="my-1 mr-2">
        <div v-if="isActionable">
          <DropDown :buttonText="actionName" :actions="actions" @select="onSelect"/>
        </div>
      </div>
      <div v-if="isSearchable" class="relative my-1">
        <label for="table-search" class="sr-only">Search</label>
        <div v-if="isSearchable">
          <FormInput type="text" icon="fas fa-search" id="table-search" v-model="search" :placeholder="`Search ${title}`" @input="searchHandler" />
        </div>
      </div>
    </div>
    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mt-2">
        <thead class="text-xs text-gray-700 bg-primary-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th  scope="col" class="p-4" v-if="1 != 1">
              <div class="flex items-center">
                <input id="checkbox-all-search" type="checkbox" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="checkbox-all-search" class="sr-only">checkbox</label>
              </div>
            </th>
            <th v-for="(heading, index) in headings" :key="index" scope="col" class="px-6 text-nowrap py-3">
              <!-- Conditionally render slot or heading -->
              <template v-if="$slots.heading">
                <slot name="heading" :heading="heading">{{ heading.title }}</slot>
              </template>
              <template v-else>
                {{ heading.title }}
              </template>
            </th>
          </tr>
        </thead>
        <tbody >
          <tr v-if="filteredData && filteredData.length" v-for="(item, index) in filteredData" :key="index" class="border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="w-4 p-4" v-if="1 != 1">
              <div class="flex items-center">
                <input :id="'checkbox-table-search-' + index" type="checkbox" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label :for="'checkbox-table-search-' + index" class="sr-only">checkbox</label>
              </div>
            </td>
            <td v-for="(value, key) in item" :key="key" class="px-6 text-nowrap py-4">
              <slot :item="item" :value="value" :index="index" :key="key" :name="key">{{ value }}</slot>
            </td>
          </tr>
          
        </tbody>
      </table>
    </div>
    <div  v-if="!filteredData?.length" class="py-12">
          <div  class="text-center flex justify-center items-center w-full overflow-hidden">
            <div class="flex flex-col items-center justify-center">
              <div class="flex items-center justify-center mx-auto mb-6 w-16 h-16 border rounded-full">
                <h1 class="fas fa-search text-xl text-gray-400"></h1>
              </div>
              <p class="mb-6 text-gray-400">You don’t have any meetings yet. <br/>Start by creating.</p>
            </div>
          </div>
    </div>
  </div>
</template>
<script setup>
import { debounce } from 'lodash-es';
const props = defineProps({
    headings: Array,
    data: {
      type: Array,
      default: () => []
    },
    actions: {
      type: Array,
      default: () => []
    },
    isActionable: {
      type: Boolean,
      default: false
    },
    isSearchable: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: null
    },
    filterTab: {
      type: Array,
      default: () => []
    },
    actionName: {
      type: String,
      default: null
    }
  });

  const emit = defineEmits(['search', 'tab-click', 'select']);

  const dropdownOpen = ref(false);
  const search = ref('');

  const filteredData = computed(() => {
    const rearrangedData = props.data.map(item => {
      const newItem = {};
      props.headings.forEach(heading => {
        newItem[heading.value] = item[heading.value];
      });
      return newItem;
    });
    return rearrangedData;
  });

  function toggleDropdown() {
    dropdownOpen.value = !dropdownOpen.value;
  }

  const searchHandler = debounce(() => {
    emit('search', search.value);
  }, 700);

  function handleTabClick(item) {
    emit('tab-click', item);
  }

  function onSelect(item) {
    emit('select', item);
  }
</script>

<style>
.empty-state {
  transform: translateY(-50%);
  top: 50%;
}

::-webkit-scrollbar {
    display: none;
}
</style>