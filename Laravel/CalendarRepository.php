<?php

namespace App\Repositories\V1\Company;
use App\Interfaces\V1\Company\CalendarInterface;
use App\Repositories\V1\Company\MicrosoftOauthRepository;
use App\Repositories\V1\Company\GoogleOauthRepository;
use App\Repositories\V1\Company\RecallRepository;
use App\Models\CalendarSetting;
use App\Models\CalendarSettingDetail;
use App\Models\Meeting;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarRepository implements CalendarInterface
{

    private MicrosoftOauthRepository $MicrosoftOauthRepository;
    private GoogleOauthRepository $GoogleOauthRepository;
    private RecallRepository $RecallRepository;
    public function __construct(MicrosoftOauthRepository $MicrosoftOauthRepository,GoogleOauthRepository $GoogleOauthRepository,RecallRepository $RecallRepository){
        $this->MicrosoftOauthRepository = $MicrosoftOauthRepository;
        $this->GoogleOauthRepository = $GoogleOauthRepository;
        $this->RecallRepository = $RecallRepository;
    }

    private $GooglePlatform = "google_calendar";
    private $MicrosoftPlatform = "microsoft_outlook";

    public function calendarConnectStatus($request){
        $user = Auth::user();
        $status = CalendarSetting::where('user_id', $user->id)->whereIn('platform', ['microsoft_outlook','google_calendar'])->get();
        $data = [];
        if(count($status)>0){
            foreach($status as $val){
                $data[$val->platform] = $val->connect_status;
            }
            $message = 'Get Calendar status.';
        }else{
            $message = 'Calendar not connected.';
        }
        return [
            'code' => 200,
            'data' => $data,
            'message' => $message,
        ];
    }

    public function connectMicrosoftCalendar($request) {

        set_time_limit(0);

        $code = $request['code'];
        $folder_id = $request['folder_id'];

        $user = Auth::user();

        $platform = $this->MicrosoftPlatform;

        $authResult = $this->MicrosoftOauthRepository->fetchAuthAndRefreshToken($code);

        $userInfo = $this->getUserCalendarEmailId($authResult['access_token'],$platform);

        $calendarSettings = $this->findUserCalendarSetting($user->id);

        if (empty($calendarSettings)) {
            return [
                'code' => 422,
                'message' => 'Please add calendar configuration settings first.',
            ];
        }

        $existingCalendar = $this->findUserCalendarByPlatform($user->id, $platform,$userInfo['mail']);

        if (!empty($existingCalendar)) {
            return [
                'code' => 422,
                'message' => 'Microsoft calendar already exists.',
            ];
        }

        $meetingSettings = $this->findUserMeetingSetting($user->id);

        if (empty($meetingSettings)) {
            $this->saveSetting($user);
        }

        $calendar = $this->RecallRepository->createMicrosoftCalendar($authResult['refresh_token']);

        if ($calendar['status'] == 'disconnected') {
            return [
                'code' => 422,
                'message' => 'Please again try to connect to Google Calendar.',
            ];
        }

        $userCalendarData = [
            'user_id' => $user->id,
            'calendar_email_id' => $calendar['oauth_email'] != null ? $calendar['oauth_email'] : $userInfo['mail'],
            'call_ai_folder_id' => $request['folder_id'],
            'recall_calendar_id' => $calendar['id'],
            'platform' => $calendar['platform'],
            'platform_access_token' => $authResult['access_token'],
            'platform_refresh_token' => $authResult['refresh_token'],
            'platform_id_token' => $authResult['id_token'],
            'connect_status' => $calendar['status']
        ];

        $userCalendar = $this->createCallAiUserCalendar($userCalendarData);

        $toDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
        $fromDate = now()->format('Y-m-d H:i:s');

        $calendarEvents = $this->RecallRepository->fetchCalendarEvents($calendar['id'],$fromDate,$toDate);

        $eventStatus = $this->saveCalendarEvents($calendarEvents['results'],$user,$folder_id);

        if($eventStatus){
            return [
                'code' => 200,
                'data' => $eventStatus,
                'message' => 'Microsoft calendar connected successfully.',
            ];
        }else{
            return [
                'code' => 404,
                'data' => null,
                'message' => 'Something went to wrong.!',
            ];
        }

    }

    public function connectGoogleCalendar($request) {

        set_time_limit(0);

        $code = $request['code'];
        $folder_id = $request['folder_id'];

        $user = Auth::user();

        $platform = $this->GooglePlatform;

        $authResult = $this->GoogleOauthRepository->fetchAuthAndRefreshToken($code);

        $userInfo = $this->getUserCalendarEmailId($authResult['access_token'],$platform);

        $calendarSettings = $this->findUserCalendarSetting($user->id);

        if (empty($calendarSettings)) {
            return [
                'code' => 422,
                'message' => 'Please add calendar configuration settings first.',
            ];
        }

        $existingCalendar = $this->findUserCalendarByPlatform($user->id, $platform,$userInfo['email']);

        if (!empty($existingCalendar)) {
            return [
                'code' => 422,
                'message' => 'Google calendar already exists.',
            ];
        }

        $meetingSettings = $this->findUserMeetingSetting($user->id);

        if (empty($meetingSettings)) {
            $this->saveSetting($user);
        }

        $calendar = $this->RecallRepository->createGoogleCalendar($authResult['refresh_token']);

        if ($calendar['status'] == 'disconnected') {
            return [
                'code' => 422,
                'message' => 'Please again try to connect to Google Calendar.',
            ];
        }

        $userCalendarData = [
            'user_id' => $user->id,
            'calendar_email_id' => $userInfo['email'],
            'call_ai_folder_id' => $request['folder_id'],
            'recall_calendar_id' => $calendar['id'],
            'platform' => $calendar['platform'],
            'platform_access_token' => $authResult['access_token'],
            'platform_refresh_token' => $authResult['refresh_token'],
            'platform_id_token' => $authResult['id_token'],
            'connect_status' => $calendar['status']
        ];

        $userCalendar = $this->createCallAiUserCalendar($userCalendarData);


        $toDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
        $fromDate = now()->format('Y-m-d H:i:s');

        $calendarEvents = $this->RecallRepository->fetchCalendarEvents($calendar['id'],$fromDate,$toDate);


        $eventStatus = $this->saveCalendarEvents($calendarEvents['results'],$user,$folder_id);

        if($eventStatus){
            return [
                'code' => 200,
                'data' => $eventStatus,
                'message' => 'Google calendar connected successfully.',
            ];
        }else{
            return [
                'code' => 404,
                'data' => null,
                'message' => 'Something want to wrong..!',
            ];
        }

    }

    public function disconnectCalendar($request){

        $user = Auth::user();
        $platform = $request->platform;

        $calendarSetting = CalendarSetting::where('user_id', $user->id)->where('platform',$platform)->where('connect_status','connecting')->first();

        if($calendarSetting){
            $meet = Meeting::where('is_type','calendar')->where('status','pending')->get();
            $getCalendarEvent = $this->RecallRepository->getListCalendarEvent($calendarSetting->recall_calendar_id);
            foreach($getCalendarEvent['results'] as $calendarEvent){
             $this->RecallRepository->deleteScheduledBotForCalendarMeeting($calendarEvent['id']);
            }
            if($platform == $this->GooglePlatform){
                $meetingPlatform = 'Google meet';
            }else if($platform == $this->MicrosoftPlatform){
                $meetingPlatform = 'Microsoft teams';
            }else{
                $meetingPlatform = '';
            }
            $this->RecallRepository->deleteCalendar($calendarSetting->recall_calendar_id);
            $meeting = Meeting::where('is_type', 'calendar')->where('status', 'pending')->where('platform',$meetingPlatform);
            if($meeting){
                $meeting->delete();
            }
            if($calendarSetting->delete()){
                return [
                    'code' => 200,
                    'data' => [],
                    'message' => 'Calendar disconnect successfully.',
                  ];
            }else{
                return [
                    'code' => 404,
                    'data' => [],
                    'message' => 'Something went wrong.',
                  ];
               }
        }else{
            return [
                'code' => 404,
                'data' => [],
                'message' => 'Data Not Found.',
            ];
        }
    }

    public function createCallAiUserCalendar($data)
    {
        return CalendarSetting::create($data);
    }

    public function getUserCalendarEmailId($token,$platform)
    {
        if($platform == $this->GooglePlatform){
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

            return $response->json();
        }

        if($platform == $this->MicrosoftPlatform){
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->get('https://graph.microsoft.com/v1.0/me');

            return $response->json();
        }
    }

    public function saveSetting($user){
        Setting::create([
            'user_id' => $user->id,
            'bot_name' => $user->name."'s Bot",
            'after_complete_run_actions' => 'nothing'
        ]);
    }

    public function findUserCalendarByPlatform($userId, $platform, $email)
    {
        return CalendarSetting::where([
            'user_id' => $userId,
            'platform' => $platform,
            'calendar_email_id' => $email
        ])->first();
    }

     public function findUserMeetingSetting($userId)
    {
        return Setting::where([
            'user_id' => $userId
        ])->first();
    }

    public function findUserCalendarSetting($userId)
    {
        return CalendarSettingDetail::where('user_id',$userId)->first();
    }

    public function saveCalendarEvents($events,$user,$folder_id){

        $bot = Setting::where(['user_id' => $user->id])->first();
        $botName  = $bot->name;
        $userName  = $user->name;
        $cout = 0;
        foreach($events as $event){
            // if(!empty($event['raw']['attendees'])){
                if(isset($event['meeting_url'])){

                    if(count($event['bots']) == 0){
                        $result = $this->RecallRepository->scheduleBotForCalendarMeeting($event,$botName,$userName);
                        $bot = $result['bots'][0];
                    }else{
                        $bot = $event['bots'][0];
                    }

                    $meeting_name = 'Event';
                    if(isset($event['raw']['summary'])){
                        $meeting_name = $event['raw']['summary'];
                    }
                    if(isset($event['raw']['subject'])){
                        $meeting_name = $event['raw']['subject'];
                    }

                    $meeting_data = [
                        'user_id' => $user->id,
                        'name' => $meeting_name,
                        'bot_id' => $bot['bot_id'],
                        'calendar_event_id' => $event['id'],
                        'folder_id' => $folder_id,
                        'meeting_link' => $bot['meeting_url'],
                        'platform' => str_replace('_', ' ', ucwords($event['meeting_platform'])),
                        'meeting_date' => $bot['start_time'],
                        'status' => 'pending',
                        'is_type' => 'calendar',
                    ];

                    $this->createCallAiMeeting($meeting_data);
                }
            // }
        }

        return true;
    }

    public function createCallAiMeeting($data)
    {
        return Meeting::create($data);
    }
}
