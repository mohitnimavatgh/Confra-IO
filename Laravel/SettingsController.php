<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Company\SettingRequest;
use App\Interfaces\V1\Company\SettingInterface;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{

    public function __construct(SettingInterface $SettingInterface)
    {
        $this->setting = $SettingInterface;
    }


    public function index()
    {
        try {
            $data = $this->setting->index();
            return $this->sendResponse($data,'Setting list get successfully.',200);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
        }
    }

    public function store(SettingRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->setting->store($request->all());
            DB::commit();
            return $this->sendResponse($data,'Setting created successfully.',200);
        }catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e, $e->getMessage() , $e->getCode());
        }
    }

}
