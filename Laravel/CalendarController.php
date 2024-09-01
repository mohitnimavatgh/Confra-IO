<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Company\CalendarRequest;
use App\Interfaces\V1\Company\CalendarInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    private CalendarInterface $CalendarInterface;
    public function __construct(CalendarInterface $CalendarInterface)
    {
        $this->CalendarInterface = $CalendarInterface;
    }

    public function calendarConnectStatus(Request $request)
    {
        try {
            $data = $this->CalendarInterface->calendarConnectStatus($request);
            if($data['code'] == 200){
                return $this->sendResponse($data['data'],$data['message'],$data['code']);
            }else{
                return $this->sendError([],$data['message'],$data['code']);
            }
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    public function connectMicrosoftCalendar(CalendarRequest $request) {
        try {
            DB::beginTransaction();
            $data = $this->CalendarInterface->connectMicrosoftCalendar($request);
            if($data['code'] == 200){
                DB::commit();
                return $this->sendResponse($data['data'],$data['message'],$data['code']);
            }else{
                DB::rollback();
                return $this->sendError([],$data['message'],$data['code']);
            }
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    public function connectGoogleCalendar(CalendarRequest $request) {
        DB::beginTransaction();
        try {
            $data = $this->CalendarInterface->connectGoogleCalendar($request);
            if($data['code'] == 200){
                DB::commit();
                return $this->sendResponse($data['data'],$data['message'],$data['code']);
            }else{
                DB::rollback();
                return $this->sendError([],$data['message'],$data['code']);
            }
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    public function disconnectCalendar(Request $request) {
        try {
            DB::beginTransaction();
            $data = $this->CalendarInterface->disconnectCalendar($request);
            if($data['code'] == 200){
                DB::commit();
                return $this->sendResponse($data['data'],$data['message'],$data['code']);
            }else{
                DB::rollback();
                return $this->sendError([],$data['message'],$data['code']);
            }
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }
}
