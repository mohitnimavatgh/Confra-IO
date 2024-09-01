<?php

namespace App\Repositories\V1\Company;
use App\Interfaces\V1\Company\MeetingInterface;
use App\Models\Meeting;
use App\Models\MeetingDetail;
use App\Models\User;
use App\Models\MeetingStatus;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class MeetingRepository implements MeetingInterface
{
    public function list($request){
        $user = Auth::user();
        $meetings = Meeting::select('folders.name as folder','folders.id as folder_id','meetings.id','meetings.name','meetings.platform','meetings.status','meetings.is_type','folders.access_type','meetings.user_id','meetings.meeting_date','meetings.meeting_link')
                            ->join('folders', 'meetings.folder_id', '=', 'folders.id');

        if(strtolower($request->meeting) == 'upcoming'){
            $action = strtolower($request->action);
            $type = strtolower($request->type);
            $meetings = $meetings->where('meetings.status', 'pending')
                        ->where('meetings.user_id', $user->id)
                        ->whereRaw("DATE(meetings.meeting_date) >= CURDATE()")
                        ->orderBy('meetings.id','desc');
            if($type == 'calendar' || $type == 'manual'){
                $meetings = $meetings->where('meetings.is_type', $type);
            }
            if(isset($request->search)){
                $meetings = $meetings->where('meetings.name','like',"%".$request->search."%");
            }

        }else if(strtolower($request->meeting) == 'recorded'){
            $company_id = $user->company_id === NULL ? $user->id : $user->company_id;
            $ids = User::where('id',$company_id)->orWhere('company_id',$company_id)->pluck('id')->toArray();
            $type = strtolower($request->type);
            $action = $request->action;

            if($type == 'all'){
                $meetings = $meetings->Where('meetings.user_id', $user->id)
                ->whereNotIn('meetings.status', ['pending', 'failed'])
                ->where('meetings.name','like',"%".$request->search."%")
                ->orderBy('meetings.id','desc');
                if(isset($action)){
                    $meetings = $meetings->where('folders.id', $action);
                }
                $meetings = $meetings->orWhere(function($query) use ($ids,$action,$request) {
                    $query->whereIn('meetings.user_id',$ids)
                    ->whereNotIn('meetings.status', ['pending', 'failed'])
                    ->where('folders.access_type', 'public')
                    ->where('meetings.name','like',"%".$request->search."%")
                    ->orderBy('meetings.id','desc');
                    if(isset($action)){
                        $query->where('folders.id', $action);
                    }
                });
            }else if($type == 'your'){
                $meetings = $meetings->where('meetings.user_id', $user->id)
                            ->whereNotIn('meetings.status', ['pending', 'failed'])
                            ->where('meetings.name','like',"%".$request->search."%")
                            ->orderBy('meetings.id','desc');
                if(isset($action)){
                    $meetings = $meetings->where('folders.id', $action);
                }
            }else if($type == 'teams'){
                $meetings = $meetings->orWhere(function($query) use ($ids,$user,$action,$request) {
                    $query->whereIn('meetings.user_id',array_diff($ids, [$user->id]))
                    ->whereNotIn('meetings.status', ['pending', 'failed'])
                    ->where('folders.access_type', 'public')
                    ->where('meetings.name','like',"%".$request->search."%")
                    ->orderBy('meetings.id','desc');
                    if(isset($action)){
                        $query->where('folders.id', $action);
                    }
                });
            }else if($type == 'failed'){
                $meetings = $meetings->where('meetings.status', 'failed')
                ->where('meetings.user_id', $user->id)
                ->where('meetings.name','like',"%".$request->search."%")
                ->orderBy('meetings.id','desc');
                if(isset($action)){
                    $meetings = $meetings->where('folders.id', $action);
                }
            }

        }
        // if(isset($request->search)){
        //     $meetings = $meetings->where('meetings.name','like',"%".$request->search."%");
        // }
        // DB::enableQueryLog();
        // $meetings = $meetings->get();
        // return DB::getQueryLog();
        $meetings = $meetings->paginate(10);
        return $meetings;
    }

    public function add($request){
        $user = Auth::user();
        $settings = Setting::where('user_id',$user->id)->first();
        if(!$settings){
            $bot_name = ucwords($user->name)."'s Bot";
            $settings = Setting::create(['bot_name' => $bot_name,'after_complete_run_actions' => 'Nothing']);
        }else {
            $bot_name = $settings->bot_name;
        }

        $input = [
            'url' => $request->meeting_link,
            'botname' => $bot_name
        ];
        $bot_id = $this->createBot($input);
        if(!empty($bot_id)){
            $meetingplatform = $this->getAppFromLink($request->meeting_link);
            $meeting = Meeting::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'folder_id' => $request->folder_id,
                'meeting_link' => $request->meeting_link,
                'platform' => $meetingplatform,
                'meeting_date' => today(),
                'status' => 'pending',
                'bot_id' => $bot_id,
            ]);

            MeetingStatus::create([
                'user_id' => $user->id,
                'meeting_id' => $meeting->id,
                'status' => 'pending',
            ]);
            return ['code' => 200,'message' => 'Meeting join successfully.','data' => $meeting];
        }
        return ['code' => 500,'message' => 'Something went wrong!','data' => []];
    }

    public function meetingUpdate($request){
        $meetingStatus = $request->status;
        $meeting = Meeting::where(['id' => $request->id,'status' => 'pending'])->first();
        if($meeting){
            // $meetingType = $this->getAppFromLink($request->meeting_link);
            $meeting->update([
                'name' => $request->name,
                'folder_id' => $request->folder_id,
                // 'meeting_link' => $request->meeting_link,
                // 'platform' => $meetingType,
                // 'meeting_date' => today(),
                // 'status' => empty($meetingStatus) ? $meeting->status : $meetingStatus,
            ]);
        }

        if($meetingStatus){
            MeetingStatus::create([
                'user_id' => Auth::user()->id,
                'meeting_id' => $meeting->id,
                'status' => $meetingStatus,
            ]);
        }
        return $meeting;
    }

    public function meetingDelete($id){
        MeetingStatus::where('meeting_id','=',$id)->delete();
        return Meeting::find($id)->delete();
    }

    public function shareFolder($request){
        $shareFolder = Meeting::find($request->meeting_id);
        if($shareFolder){
            $shareFolder->update([
               'folder_id'=>$request->folder_id,
            ]);
        }
        return true;
    }

    public function getAppFromLink($link) {
        if (strpos($link, 'zoom.us') !== false) {
            return 'Zoom';
        } elseif (strpos($link, 'meet.google.com') !== false) {
            return 'Google Meet';
        } elseif (strpos($link, 'skype.com') !== false) {
            return 'Skype';
        } elseif (strpos($link, 'teams.live.com') !== false) {
            return 'Teams';
        } elseif (strpos($link, 'webex.com') !== false) {
            return 'WebEx';
        }else {
            return 'Unknown';
        }
    }
    public function createBot($request) {

        $client = new \GuzzleHttp\Client();

        $transcription = '{"bot_name":"'.$request['botname'].'","real_time_transcription":{"partial_results":false,"destination_url":"'.$request['url'].'"},"real_time_media":{"websocket_speaker_timeline_exclude_null_speaker":true},"chat":{"on_bot_join":{"send_to":"everyone_except_host","message":"'.$request['botname'].'"},"on_participant_join":{"exclude_host":true,"message":"'.$request['botname'].'"}},"automatic_video_output":{"in_call_recording":{"kind":"jpeg","b64_data":"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"}},"recording_mode":"gallery_view_v2","recording_mode_options":{"participant_video_when_screenshare":"hide"}, "transcription_options": {"provider": "meeting_captions"},"meeting_url":"'.$request['url'].'"}';

        $token = config('callAi.RECALL_TOKEN');

        $response = $client->request('POST', 'https://us-west-2.recall.ai/api/v1/bot/', [
            'body' => $transcription,
            'headers' => [
                'Authorization' => 'Token '.$token,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
        ]);

       $callBotRes = json_decode($response->getBody());
       return $callBotRes->id;
    }
}
