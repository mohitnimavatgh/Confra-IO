<?php

namespace App\Repositories\V1\Company;
use App\Interfaces\V1\Company\ChatToCallInterface;
use App\Repositories\V1\Company\RecallRepository;
use App\Models\ChatToCall;
use App\Models\ChatToCallHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\QuickQuestion;
use App\Models\MeetingDetail;

class ChatToCallRepository implements ChatToCallInterface
{

    private RecallRepository $RecallRepository;
    public function __construct(RecallRepository $RecallRepository){
        $this->RecallRepository = $RecallRepository;
    }

    public function storeChat($request){
        $chatToCall_id = $request->chat_to_call_id;
        $quick_question = $request->question;
        $meetingId = $request->meeting_id;

        if(isset($request->quick_question_id)){
            $quick_question = QuickQuestion::find($request->quick_question_id)->name;
        }
 
        $openAi = $this->openAiCall($quick_question,$meetingId);
        // dd($openAi);
        $data = [];
        if($openAi['status']){
            $answer = $openAi['data'];
            $chatToCallHistory = null;
            if(empty($chatToCall_id)){
                $chatTocall = ChatToCall::create([
                    'user_id' => Auth::user()->id,
                    'meeting_ids' => $meetingId,
                    'title' => $request->title,
                ]);
                $chatToCallHistory = $this->chatToCallHistory($request,$chatTocall->id,$answer);
            }else{
                $chatToCallHistory = $this->chatToCallHistory($request,$chatToCall_id,$answer);
            }
            $data['status'] = true;
            $data['data'] = $chatToCallHistory;
        }else{
            $data = $openAi;
        }

        return $data;
    }

    public function chatToCallHistory($request,$chat_to_call_id,$answer = null){
        $chatToCallHistory = ChatToCallHistory::create([
            'chat_to_call_id' => $chat_to_call_id,
            'quick_question_id' => $request->quick_question_id,
            'question' => $request->question,
            'answer' => $answer,
            'type' => empty($request->type) ? 'single' : $request->type,
        ]);
        return $chatToCallHistory;
    }

    public function UpdateChatTitle($request){
        $chatToCall = ChatToCall::find($request->id);
        if($chatToCall){
            $chatToCall->update([
                'title' => $request->title,
            ]);
        }
        return $chatToCall;
    }

    public function openAiCall($question,$meeting_id){
        $meeting = MeetingDetail::where('meeting_id',$meeting_id)->first();
        $data = $this->RecallRepository->getMeetingDetails($meeting->transcribe,$question);
        return [
            'status' => 200,
            'data' => $data
        ];
    }

    public function getChatMessage($request){
        $chat = ChatToCall::select('chat_to_call_histories.id','chat_to_call_histories.chat_to_call_id','chat_to_call_histories.answer')
                ->selectRaw('CASE
                            WHEN chat_to_call_histories.quick_question_id is null THEN chat_to_call_histories.question
                            ELSE quick_questions.name
                            END AS question'
                            )
                ->join('chat_to_call_histories','chat_to_call_histories.chat_to_call_id','=','chat_to_calls.id')
                ->leftJoin('quick_questions','quick_questions.id','=','chat_to_call_histories.quick_question_id')
                ->where('chat_to_calls.meeting_ids',$request->meeting_id)
                ->where('chat_to_call_histories.chat_to_call_id',$request->history_id)
                ->orderBy('chat_to_call_histories.id','ASC')
                ->get();
        return $chat;
    }

    public function sendChatEmail($request){
        $data = $this->getChatMessage($request);
        $user = Auth::user();
        $chatString = '';
        foreach ($data as $chat) {
            $chatString .= "Q:- " . $chat['question'] . "\nA:- " . $chat['answer'] . "\n\n";
        }
        $rep = mailSend($user->email,$chatString,'auth.emails.downloadChatEmail');
        if($rep){
            return [
                'code' => 200,
                'message' => 'success',
                'data' => true
            ];
        }else{
            return [
                'code' => 404,
                'message' => 'Something went wrong',
            ];
        }
    }
}
