<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\EmployeeCanteenCard;
use Illuminate\Support\Facades\Http;
use URL;
use App\Models\EmployeeCanteenTransaction;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportCanteenTransaction;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\CanteenTapLog;
class CanteenController extends Controller
{

    public function generate_card_all_employee(Request $request){
        $employee_data = Employee::where("is_admin", 0)->get();
        foreach ($employee_data as $key => $value) {
            $canteen_card_exist = EmployeeCanteenCard::where("employee_id", $value->id)->count();
            if ($canteen_card_exist == 0) {
                $data = [
                    'employee_id' => $value->employee_id,
                    'status' => 1,
                    "access_breakfast" => 1,
                    "access_lunch" => 1,
                    "access_dinner" => 1,
                    'created_by' => 1,
                ];
                EmployeeCanteenCard::create($data);
            }
        }
    }

    public function get_last_canteen_trx(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $last_canteen_trx = EmployeeCanteenTransaction::orderBy("id","desc");
        $response_data = ["last_id"=>0];
        if ($last_canteen_trx->count() != 0) {
            $response_data["last_id"] = $last_canteen_trx->first()->id;
        }
        return json_encode($response_data);
    }

    public function upload_canteen_trx(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m" || $request->data == null) {
            return;
        }
        $data = $request->data;
        foreach ($data as $key => $value) {
            $value["created_at"] = Carbon::createFromTimeStamp(strtotime($value["created_at"]))->toDateTimeString();
            EmployeeCanteenTransaction::create($value);
        }
    }

    public function sync_canteen(Request $request){
        $response = Http::post('http://ipp-kalteng1.com/api/get_last_canteen_trx', [
            'key_code' => 'T()tt3nh@m',
        ]);
        $result = json_decode($response);
        $get_last_id_from_server = $result->last_id;
        $canteen_trx_data = EmployeeCanteenTransaction::where([["id",">",$get_last_id_from_server]])->get();
        $post_data_to_server = Http::post('http://ipp-kalteng1.com/api/upload_canteen_trx', [
            'key_code' => 'T()tt3nh@m',
            'data' => $canteen_trx_data,
        ]);
        return $post_data_to_server;
    }

    public function sync_slp(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $response = Http::post('http://ipp-kalteng1.com/api/get_unpull_slp_trx', [
            'key_code' => 'T()tt3nh@m',
        ]);
        $result = json_decode($response);
        foreach ($result as $key => $value) {
            $value->created_at = Carbon::createFromTimeStamp(strtotime($value->created_at))->toDateTimeString();
            if ($value->updated_at != null) {
                $value->updated_at = Carbon::createFromTimeStamp(strtotime($value->updated_at))->toDateTimeString();
            }
            $check_leave_exist = LeaveDetail::where("id", $value->id);
            if ($check_leave_exist->count() == 1) {
                LeaveDetail::where("id", $value->id)->update(json_decode(json_encode($value), true));
            }else{
                LeaveDetail::create(json_decode(json_encode($value), true));
            }
        }
    }

    public function sync_employee(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $response = Http::post('http://ipp-kalteng1.com/api/get_unpull_employee_trx', [
            'key_code' => 'T()tt3nh@m',
        ]);
        $result = json_decode($response);
        foreach ($result as $key => $value) {
            $value->created_at = Carbon::createFromTimeStamp(strtotime($value->created_at))->toDateTimeString();
            if ($value->updated_at != null) {
                $value->updated_at = Carbon::createFromTimeStamp(strtotime($value->updated_at))->toDateTimeString();
            }
            $check_employee_exist = Employee::where("id", $value->id);
            if ($check_employee_exist->count() == 1) {
                Employee::where("id", $value->id)->update(json_decode(json_encode($value), true));
            }else{
                Employee::create(json_decode(json_encode($value), true));
            }
        }
    }

    public function get_unpull_slp_trx(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $slp_data = Leave::where([["app_status", ">=", "4"], "category"=>2])->get();
        $leave_detail_data = [];
        foreach ($slp_data as $key => $value) {
            foreach (LeaveDetail::where("leave_id",$value->id)->get() as $key2 => $value2) {
                array_push($leave_detail_data, $value2);
            }
        }
        return json_encode($leave_detail_data);
    }

    public function get_unpull_employee_trx(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $employee_data = Employee::where("is_admin",0)->get();
        return json_encode($employee_data);
    }

    public function sync_employee_cc(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $response = Http::post('http://ipp-kalteng1.com/api/get_unpull_employee_cc_trx', [
            'key_code' => 'T()tt3nh@m',
        ]);
        $result = json_decode($response);
        foreach ($result as $key => $value) {
            $value->created_at = Carbon::createFromTimeStamp(strtotime($value->created_at))->toDateTimeString();
            if ($value->updated_at != null) {
                $value->updated_at = Carbon::createFromTimeStamp(strtotime($value->updated_at))->toDateTimeString();
            }
            $check_employee_cc_exist = EmployeeCanteenCard::where("id", $value->id);
            if ($check_employee_cc_exist->count() == 1) {
                EmployeeCanteenCard::where("id", $value->id)->update(json_decode(json_encode($value), true));
            }else{
                EmployeeCanteenCard::create(json_decode(json_encode($value), true));
            }
        }
    }

    public function get_unpull_employee_cc_trx(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $employee_cc_data = EmployeeCanteenCard::get();
        return json_encode($employee_cc_data);
    }

    public function get_last_log(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m") {
            return;
        }
        $last_log = CanteenTapLog::orderBy("id","desc");
        $response_data = ["last_id"=>0];
        if ($last_log->count() != 0) {
            $response_data["last_id"] = $last_log->first()->id;
        }
        return json_encode($response_data);
    }

    public function upload_log(Request $request){
        if ($request->key_code == null || $request->key_code != "T()tt3nh@m" || $request->data == null) {
            return;
        }
        $data = $request->data;
        foreach ($data as $key => $value) {
            $value["created_at"] = Carbon::createFromTimeStamp(strtotime($value["created_at"]))->toDateTimeString();
            CanteenTapLog::create($value);
        }
    }

    public function sync_log(Request $request){
        $response = Http::post('http://ipp-kalteng1.com/api/get_last_log', [
            'key_code' => 'T()tt3nh@m',
        ]);
        $result = json_decode($response);
        $get_last_id_from_server = $result->last_id;;
        $log_data = CanteenTapLog::where([["id",">",$get_last_id_from_server]])->get();
        $post_data_to_server = Http::post('http://ipp-kalteng1.com/api/upload_log', [
            'key_code' => 'T()tt3nh@m',
            'data' => $log_data,
        ]);
        return $post_data_to_server;
    }
}
