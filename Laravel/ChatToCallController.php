<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Interfaces\V1\Company\ChatToCallInterface;
use App\Http\Requests\V1\Company\ChatToCallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatToCallController extends Controller
{
    public function __construct(ChatToCallInterface $chatToCallInterface)
    {
        $this->chatTocall = $chatToCallInterface;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->chatTocall->getChatMessage($request);
            if($data){
                return $this->sendResponse($data,'Chat list get successfully.',200);
            }
            return $this->sendError('Something want to wrong..!',[],404);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , 500);
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
    public function store(ChatToCallRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->chatTocall->storeChat($request);
            if($data){
                DB::commit();
                return $this->sendResponse($data,'Chat created successfully.',200);
            }
            DB::rollback();
            return $this->sendError('Something want to wrong..!',[],404);
        }catch (\Exception $e) {
            DB::rollback();
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->chatTocall->UpdateChatTitle($request);
            DB::commit();
            return $this->sendResponse($data,'Chat title updated successfully.',200);
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
        //
    }

    public function sendChatEmail(Request $request){
        try {
            $data = $this->chatTocall->sendChatEmail($request);
            if($data['code'] == 200){
                return $this->sendResponse($data['data'],'Chat email send successfully.',200);
            }else{
                return $this->sendResponse([], "Something went wrong!", 500);
            }
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , 500);
        }
    }
}
