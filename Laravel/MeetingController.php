<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Interfaces\V1\Company\MeetingInterface;
use App\Http\Requests\V1\Company\JoinMeetingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    public function __construct(MeetingInterface $MeetingInterface)
    {
        $this->meeting = $MeetingInterface;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->meeting->list($request);
            return $this->sendResponse($data,'Meeting list get successfully.',200);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JoinMeetingRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->meeting->add($request);
            if($data['code'] == 200){
                DB::commit();
                return $this->sendResponse($data['data'],$data['message'],200);
            }else{
                DB::rollback();
                return $this->sendResponse([], $data['message'], 500);
            }
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function shareFolder(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->meeting->shareFolder($request);
            DB::commit();
            return $this->sendResponse($data,'Folder share successfully.',200);
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(JoinMeetingRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->meeting->meetingUpdate($request);
            DB::commit();
            return $this->sendResponse($data,'Meeting Updated successfully.',200);
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $data = $this->meeting->meetingDelete($id);
            DB::commit();
            return $this->sendResponse($data,'Meeting deleted successfully.',200);
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }
}
