<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\Admin\AdminInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\AddUserRequest;

class AdminController extends Controller
{
    public function __construct(AdminInterface $AdminInterface)
    {
        $this->admin = $AdminInterface;
    }

    public function getUser(){
        try {
            return $this->admin->getUser();
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function addUser(AddUserRequest $request){
        try {
            return $this->admin->addUser($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function editUser(AddUserRequest $request){
        try {
            return $this->admin->editUser($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }

    public function deleteUser(Request $request){
        try {
            $validator = Validator::make($request->all(),['user_id' => 'required']);
            if ($validator->fails())
            {
                return $this->sendError('Validation errors',$validator->errors(),422);
            }
            return $this->admin->deleteUser($request);
        }catch (\Exception $e) {
           return $this->sendError($e, $e->getMessage() , $e->getCode());
       }
    }
}
