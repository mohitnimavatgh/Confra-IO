<script setup lang="ts">
import { useCalendar } from "@/stores/user/calendar";
import { useFolders } from "@/stores/user/folders";
import { useVuelidate } from "@vuelidate/core";
import { required,helpers } from "@vuelidate/validators";
import { useLoader } from "@/stores/loader";
definePageMeta({
       middleware: "is-authenticate",
})
const route = useRoute();
const router = useRouter()
const { $toast } = useNuxtApp()
const calendar = useCalendar()
const folders = useFolders()
const loader = useLoader();
const google_client_id = import.meta.env.VITE_GOOGLE_CALENDAR_CLIENT_ID;
const joinModal = ref(false);
const microsoftTeamsCode = ref('')
const modalType = ref('')
const googleCalendarLoading = ref(false)
const googleBtnDisabled = ref(false)
const microsoftBtnDisabled = ref(false)
const microsoftCalendarLoading = ref(false)
const calendarSettings = ref({
    is_saved: true,
    record_all:true,
    record_confirmed:true,
    record_external:true,
    record_im_host:true,
    record_im_not_host:true,
    record_internal:true,
    record_recurring:true
})

const folder = ref({
    folder_id: '',
})
const rules = {
    folder: {       
        folder_id: {
          required: helpers.withMessage("The Folder field is required", required),
        }
    }
}
const v$ = useVuelidate(rules, {folder})

const setCalendarOption = () => {
    let data = calendar.calendarSettingData;
    calendarSettings.value.is_saved = data.is_saved ? true : false
    calendarSettings.value.record_all = data.record_all ? true : false
    calendarSettings.value.record_confirmed = data.record_confirmed ? true : false
    calendarSettings.value.record_external = data.record_external ? true : false
    calendarSettings.value.record_im_host = data.record_im_host ? true : false
    calendarSettings.value.record_im_not_host = data.record_im_not_host ? true : false
    calendarSettings.value.record_internal = data.record_internal ? true : false
    calendarSettings.value.record_recurring = data.record_recurring ? true : false
}

const openModal = (type) => {
    modalType.value = type;
    joinModal.value = !joinModal.value
}

const closeModal = () => {
    folder.value.folder_id = ''
    v$.value.$reset();
    joinModal.value = false
}

const getCalendarSetting = () => {
    if(calendar.calendarSettingData == null) {
        loader.loading = true
        calendar.calendarSetting().then((resp:any) => {
            if(resp.success){
                loader.loading = false
                if(resp.data == null) {
                    saveCalendarSetting();
                }else{
                    setCalendarOption();    
                }
            }  
        })
    }else{
        setCalendarOption();
    }
}

const saveCalendarSetting = () => {
    loader.loading = true
    calendar.update(calendarSettings.value).then((resp:any) => {        
        setCalendarOption(); 
        loader.loading = false
        $toast.success('Calendar Meeting Settings Updated', { duration: 10000 })       
    }).catch((error) => {
        catchResponse(error)             
    })
}

const getFolderList = () => {
    loader.loading = true
    folders.list({search:''}).then((resp:any) => {
        loader.loading = false
    }).catch((error) => {      
        catchResponse(error)  
    })
}

const getCalendarStatus = () => {
    loader.loading = true
    calendar.calendarStatus().then(() => {
        loader.loading = false
    }).catch((error) => {       
        catchResponse(error)  
    });
}

const calendarStatusHandle = (platform:any)=>{
    if(platform == 'google_calendar'){
        googleCalendarLoading.value = !googleCalendarLoading.value
        googleBtnDisabled.value = !googleBtnDisabled.value
        !googleBtnDisabled.value ? resetData() : ''            
    }else{
        microsoftCalendarLoading.value = !microsoftCalendarLoading.value
        microsoftBtnDisabled.value = !microsoftBtnDisabled.value
        !microsoftBtnDisabled.value ? resetData() : '' 
    }
}

const getCodeGoogleCalendar = async () =>{
    const result = await v$.value.$validate()
    if (result) {  
        localStorage.setItem('folder_id',folder.value.folder_id)
        const params = {
            client_id: google_client_id,
            redirect_uri: `${import.meta.env.VITE_APP_URL}/call-ai/settings/calendar`,
            response_type: "code",
            scope: [
                "https://www.googleapis.com/auth/calendar.events.readonly",
                "https://www.googleapis.com/auth/userinfo.email",
                ].join(" "),
            access_type: "offline",
            prompt: "consent",
            state: "user-Id1",
        };
        const url = new URL("https://accounts.google.com/o/oauth2/v2/auth");
        url.search = new URLSearchParams(params).toString();   
        let redirectUrl = url.toString();    
        window.location.href = redirectUrl;
    }
}

const googleConnecting = () => {    
    folder.value.code = route.query.code
    folder.value.folder_id = localStorage.getItem('folder_id')
    calendar.google(folder.value).then((resp:any) => {       
        $toast.success('google calendar account connected..', { duration: 5000 })
        calendarStatusHandle('google_calendar')
        calendar.google_calendar_connection = true;
        router.push('/call-ai/settings/calendar');        
    }).catch((error) => {
        calendarStatusHandle('google_calendar')
        catchResponse(error)               
    })
}

const getCodeMicrosoftTeamsCalendar = async () => {
    const result = await v$.value.$validate()
    if(result){
        localStorage.setItem('folder_id',folder.value.folder_id)
        const params = {
            client_id: 'b547715d-e50f-4ca6-9184-cd206d549cdd',
            redirect_uri: `${import.meta.env.VITE_APP_URL}/call-ai/settings/calendar`,
            response_type: 'code',
            scope: 'offline_access openid email Calendars.ReadWrite'       
        };
        const url = new URL("https://login.microsoftonline.com/common/oauth2/v2.0/authorize?");
        url.search = new URLSearchParams(params).toString();   
        let redirectUrl = url.toString();    
        window.location.href = redirectUrl;
    }
}

const microsoftConnecting = () =>{    
    calendar.microsoftTeams({code :microsoftTeamsCode.value}).then((resp:any) => {
        if(resp?.success) {   
            $toast.success('microsoft teams calendar account connected..', { duration: 5000 })
            calendarStatusHandle('microsoft_outlook')
            calendar.microsoft_calendar_connection = true;
            router.push('/call-ai/settings/calendar'); 
        }
    }).catch((error) => {
        calendarStatusHandle('microsoft_outlook')
        catchResponse(error)       
    })
}

const disconnectedCalendar = (platformType:any) => {
    let data = {
        platform: platformType 
    }
    calendarStatusHandle(platformType)
    calendar.disconnectGoogleCalendar(data).then((resp:any) => {       
        $toast.success('disconnect calendar...', { duration: 5000 })
        calendarStatusHandle(platformType)
        if(platformType == 'google_calendar'){
            calendar.google_calendar_connection = false;
        }else{
            calendar.microsoft_calendar_connection = false;
        }      
    }).catch((error) => {
        calendarStatusHandle(platformType)        
        catchResponse(error)               
    })
}

const catchResponse = (err: any) => {
    loader.loading = false
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

const resetData = () => {
    folder.value.folder_id = ''
    v$.value.$reset()
}

onMounted(async () => {
    await nextTick();
    await getCalendarSetting();
    await getFolderList();
    await getCalendarStatus()
    if(route.query.code && Object.keys(route.query).length > 1){
        calendarStatusHandle('google_calendar')
        googleConnecting();
    }else if(route.query.code && Object.keys(route.query).length == 1){
        microsoftTeamsCode.value = route.query.code
        calendarStatusHandle('microsoft_outlook')
        microsoftConnecting();
    }
})

</script>
<template>
    <div class="mt-5">
        <div>
            <h4 class="text-lg font-semibold text-gray-600 dark:text-white">Configure Your Calendar</h4>
            <p class="text-xs font-normal text-gray-500 mt-0.5">Get started by connecting your calendar account </p>
        </div>
        <div class="flex flex-col lg:flex-row bg-white mt-3 box bg-white dark:bg-gray-800">
            <div class="flex-1 p-5 flex flex-col justify-center items-center">
                <div class="flex items-center mb-3">
                    <img class="w-6 mr-2" src="@/assets/image/google-calendar.png" alt="Google Calendar"/>
                    <span class="font-medium text-gray-500 text-sm">Google Calendar</span>
                </div>
                <div class="flex relative items-center mt-2">
                    <Button :text="'Connect'" outline class="mr-2" v-if="!calendar.google_calendar_connection" :disabled="googleBtnDisabled" @click="openModal('google')" />
                    <Button :text="'Disconnect'"  v-if="calendar.google_calendar_connection" :disabled="googleBtnDisabled" @click="disconnectedCalendar('google_calendar')" class="mr-2" />
                    <div class="absolute -right-20 sm:-right-24 flex items-center"  v-if="googleCalendarLoading">
                        <svg aria-hidden="true" class="w-2 h-2 sm:w-4 sm:h-4 text-gray-50 animate-spin dark:text-gray-600 fill-primary-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                    </svg>
                    <span class="ml-2 text-sm" >Loading..</span>
                    </div>
                </div>               
            </div>
            <div class="lg:border-l border-gray-300 lg:flex-1 p-5 flex flex-col justify-center items-center">
                <div class="flex items-center mb-3">
                    <img class="w-6 mr-2" src="@/assets/image/teams-calendar.png" alt="Microsoft Teams Calendar"/>
                    <span class="font-medium text-gray-500 text-sm">Microsoft Teams Calendar</span>
                </div>
                <div class="flex relative items-center mt-2">
                    <Button :text="'Connect'" outline class="mr-2" :disabled="microsoftBtnDisabled" v-if="!calendar.microsoft_calendar_connection"  @click="openModal('microsoft')" />
                    <Button :text="'Disconnect'"  v-else class="mr-2" :disabled="microsoftBtnDisabled" />
                    <div class="absolute -right-20 sm:-right-24 flex items-center" v-if="microsoftCalendarLoading">
                        <svg aria-hidden="true" class="w-2 h-2 sm:w-4 sm:h-4 text-gray-50 animate-spin dark:text-gray-600 fill-primary-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="ml-2 text-sm" >Loading..</span>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <p class="text-sm font-normal text-gray-500 mt-3">Change Your Calendar Meeting Settings</p>
        </div>        
        <Modal :title="'Select Folder'" :show="joinModal" @close="closeModal()">
            <div class="modal-content  p-4 md:p-5">           
                <div class="col-span-2 mb-3">
                    <FormSelect label="Folder" placeholder="Select Folder" id="folder" v-model="v$.folder.folder_id.$model" :errors="v$.folder.folder_id.$errors" name="folder" :options="folders.folders" />
                </div>
            </div>
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
                <Button class="mr-2" :text="'Select Folder'" frontIcon="fas fa-plus" v-if="modalType == 'google'" @click="getCodeGoogleCalendar()" />
                <Button class="mr-2" :text="'Select Folder'" frontIcon="fas fa-plus" v-if="modalType == 'microsoft'"@click="getCodeMicrosoftTeamsCalendar()" />
                <Button :text="'Cancel'" @click="closeModal()" outline/>
            </div>
        </Modal>
        <div class="box w-full mt-2 bg-white dark:bg-gray-800">
            <div class="flex justify-between p-3 bg-primary-50 mt-2 rounded-t-lg">
                <span class="font-medium text-gray-500 text-sm">Record All Meetings</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_all" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-between p-3">
                <span class="font-medium text-gray-500 text-sm">Record meetings where I am the host</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_im_host" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-between p-3">
                <span class="font-medium text-gray-500 text-sm">Record meetings where I am not the host</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_im_not_host" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-between p-3">
                <span class="font-medium text-gray-500 text-sm">Record external meetings</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_external" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-between p-3">
                <span class="font-medium text-gray-500 text-sm">Record internal meetings</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_internal" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-between p-3">
                <span class="font-medium text-gray-500 text-sm">Record confirmed-only meetings</span>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="calendarSettings.record_confirmed" class="sr-only peer">
                    <div class="relative w-8 h-4 bg-gray-200 dark:peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:end-[-2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                </label>
            </div>
            <div class="flex justify-end  mt-4">
                <Button :text="'Save'" class="flex text-center mr-2" @click="saveCalendarSetting()" outline/>
            </div>
        </div>
    </div>
</template>