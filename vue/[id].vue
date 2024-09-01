<script setup lang="ts">
import { useMeetings } from "@/stores/user/meetings";
definePageMeta({
       middleware: "is-authenticate",
})
const meetings = useMeetings()
import { useRoute } from 'vue-router';
const route = useRoute();
const selectedTab = ref('Calls')
const tabItems = ref([
    { label: 'Calls', icon: 'fas fa-phone' },
    { label: 'Chat To Call', icon: 'fas fa-comment' },
    { label: 'History', icon: 'fas fa-clock-rotate-left' }
]);

const tabChanged = (item) => {
    selectedTab.value = item.label
}

onMounted(async () => {
  await nextTick()
  await getMeetingDetail(route.params.id)
});

const getMeetingDetail = async (id) => {
    await meetings.meetingDetail({ meeting_id: id })
}

const meetingDetail = computed(() => meetings.getMeetingDetail);

</script>
<template>
    <div>
        <tab-button-group :items="tabItems" @tab-click="tabChanged" :selectedTab="selectedTab"/>
        <CallDetail v-if="selectedTab == 'Calls'"  :meetingDetail="meetingDetail"  />
        <CallChat v-if="selectedTab == 'Chat To Call'"  :meetingDetail="meetingDetail" />
        <CallHistory v-if="selectedTab == 'History'" />
    </div>
</template>
