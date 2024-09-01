<?php

namespace App\Interfaces\V1\Company;


interface MeetingInterface {
    public function list($request);
    public function add($request);
    public function meetingUpdate($request);
    public function shareFolder($request);
    public function meetingDelete($id);
}
