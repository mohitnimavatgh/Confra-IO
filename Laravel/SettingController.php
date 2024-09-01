<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\SettingInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\SettingRequest;

class SettingController extends Controller
{
    public function __construct(SettingInterface $SettingInterface)
    {
        $this->setting = $SettingInterface;
    }

    public function getSetting(){
        try {
            return $this->setting->getSetting();
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function addSetting(SettingRequest $request){
        try {
            return $this->setting->addSetting($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function editSetting(SettingRequest $request){
        try {
            return $this->setting->editSetting($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function deleteSetting(Request $request){
        try {
            $validator = Validator::make($request->all(),['setting_id' => 'required']);
            if ($validator->fails())
            {
                return $this->sendError('Validation errors',$validator->errors(),422);
            }
            return $this->setting->deleteSetting($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

}
