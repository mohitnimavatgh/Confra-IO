<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\V1\Company\CalendarSettingInterface;
use Illuminate\Support\Facades\DB;

class CalendarSettingController extends Controller
{
    private CalendarSettingInterface $calendarSetting;
    public function __construct(CalendarSettingInterface $calendarSetting)
    {
        $this->calendarSetting = $calendarSetting;
    }

    public function index()
    {
        try {
            $data = $this->calendarSetting->index();
            return $this->sendResponse($data,'Calendar Setting list get successfully.',200);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->calendarSetting->update($request);
            DB::commit();
            return $this->sendResponse($data,'Calendar Setting updated successfully.',200);
        }catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e, $e->getMessage() , $e->getCode());
        }
    }

}
