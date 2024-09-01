import { defineStore } from 'pinia'
import {
  apiCreateMeeting,
  apiGetUpcomingMeeting,
  apiDeleteMeeting,
  apiGetRecordedMeeting,
  apiGetMeetingDetail,
  apiShareMeeting,
  apiCreateNote,
  apiUpdateMeeting
} from '@/API/utils'

export const useMeetings = defineStore('meetings', {
  state: () => ({
    upcoming: null,
    recorded: null,
    getMeetingDetail: null,
  }),
  actions: {
    async create(bot: any) {
      try {
        const response = await apiCreateMeeting(bot);
        if (response.success) {
          const responseData = response.data;
          return responseData;
        }
      } catch (error) {
        throw error;
      }
    },
    async update(bot: any) {
      try {
        const response = await apiUpdateMeeting(bot)
        const responseData = response.data;
        return responseData;
      } catch (error) {
        throw error;
      }
    },
    async upcomingMeeting(search: any) {
      try {
        const response = await apiGetUpcomingMeeting(search);
        const responseData = response.data;
        if (response.success) {
          this.upcoming = responseData;
          return responseData;
        }
      } catch (error) {
        throw error;
      }
    },
    async recordedMeeting(search: any) {
      try {
        const response = await apiGetRecordedMeeting(search)
        const responseData = response.data;
        if(response.success){
          this.recorded = responseData;
        }
        return responseData;
      } catch (error) {
        throw error;
      }
    },
    async meetingDetail(id: any) {
      try {
        const response = await apiGetMeetingDetail(id)
        const responseData = response.data;
        if(response.success){
          this.getMeetingDetail = responseData;
        }
        return responseData;
      } catch (error) {
        throw error;
      }
    },
    async delete(meetingId: any) {
      try {
        const response = await apiDeleteMeeting(meetingId);
        if (response.success) {
          const responseData = response.data;
          return responseData;
        }
      } catch (error) {
        throw error;
      }
    },
    async shareMeeting(shareMeetingData: any) {
      try {
        const response = await apiShareMeeting(shareMeetingData)
        if (response.success) {
          const responseData = response.data;
          return responseData;
        }
      } catch (error) {
        throw error;
      }
    },
    async notes(notes: any) {
      try {
        const response = await apiCreateNote(notes)
        const responseData = response.data;
        return responseData;
      } catch (error) {
        throw error;
      }
    }
  },
  persist: {
    storage: persistedState.localStorage,
  },
});
