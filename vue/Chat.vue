<script setup lang="ts">
import { useQuickQuestions } from "@/stores/user/quickQuestions";
import { useChatToCall } from "@/stores/user/chatToCall";
import { useRoute } from 'vue-router';
const route = useRoute();
const quickQuestions = useQuickQuestions()
const chatToCall = useChatToCall()
const { $toast } = useNuxtApp()
const props = defineProps({
    meetingDetail: null,
});
const meetingId = ref(route.params.id)

const chat = ref({
    meeting_id: meetingId.value,
    chat_to_call_id: chatToCall.chatId,
    quick_question_id: null,
    question: '',
})

const detail = ref(null)
const faqsList = computed(() => {
    detail.value = props.meetingDetail
    if (props.meetingDetail?.faqs) {
        return JSON.parse(JSON.parse(props.meetingDetail?.faqs))
    }
    return [];
});

const quickQuestionParams = { page: null, search: '' }
const chatToCallParams = { meeting_id: meetingId.value }

onMounted(async () => {
    await nextTick()
    await getChatToCall()
    await getQuickQuestions()
});

const getQuickQuestions = () => {
    quickQuestions.list(quickQuestionParams)
}
const getChatToCall = () => {
    chatToCall.list(chatToCallParams)
}
const quickQuestionPageChange = (page: any) => {
    quickQuestionParams.page = page
    getQuickQuestions()
};

const quickQuestionLists = computed(() => quickQuestions.allQuickQuestions);
const chatToCallLists = computed(() => chatToCall.getChatList);

const handleKeyEvent = () => {
    chat.value.quick_question_id = null;
}

const sendMessage = () => {
    chatToCall.create(chat.value).then((resp:any) => {
        chat.value.question = null;
        //@ts-ignore
        document.getElementById("question").value = null;
        getChatToCall();
    }).catch((error)=>{
        catchResponse(error)
    })
}

const catchResponse = (err: any) => {
  if(err?.response?.status == 422){
    let data = err?.response?.data?.data
    if(data){
        let keys = Object.keys(data)[0];
        let firstValue = data[keys];
        $toast.error(firstValue[0], { duration: 5000 })
    }else{
        if(!err?.response?.data?.success){
            $toast.error(err?.response?.data?.message, { duration: 5000 })
        }else{
            $toast.error('something went wrong...!', { duration: 5000 })
        }
    }
  }else{
    if(!err?.response?.data?.success){
        $toast.error(err?.response?.data?.message, { duration: 5000 })
    }else{
        $toast.error('something went wrong...!', { duration: 5000 })
    }
  }  
}

const quickQuestionCall = (item : any) => {    
    chat.value.question = null;
    chat.value.quick_question_id = item.id;
    //@ts-ignore
    document.getElementById("question").value = item.name;
}

</script>
<template>
    <div class="mt-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="col-span-2 ">
                <div class="bg-white rounded rounded-lg">
                    <div class="p-5 h-600 min-h-600 overflow-y-scroll ">
                        <CallChatTiles :lists="chatToCallLists" />
                    </div>
                    <div class="p-4">
                        <FormInput size="large" id="question" v-model="chat.question" placeholder="Ask Someting"
                            @keypress="handleKeyEvent()" @submitChat="sendMessage()" />
                    </div>
                </div>
                <div class="mt-5">
                    <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-white">Quick Questions</label>
                    <div class="bg-white rounded-lg p-5 text-gray-600 text-sm leading-7">
                        <ul>
                            <li v-for="quickQuestion in quickQuestionLists" :key="index">
                                <button type="button" @click="quickQuestionCall(quickQuestion)"
                                    class="text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:focus:ring-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700 me-2 mb-2">
                                    {{ quickQuestion?.name }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div>
                <div class="bg-white rounded">
                    <Accordion :title="`Meeting FAQ's`" :icon="'fas fa-circle-question'" :accordions="faqsList" />
                </div>
            </div>
        </div>
</div></template>
