<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Whatsapp;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\UserRole;
use App\Models\UserAccount;
use App\Models\LeaveApprovalWhatsapp;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'leaves';
    protected $fillable = ['id','employee_id','slpdate','category','type','prev_date','from_date','to_date','total_day','total_taken','return_date','purpose','destination','transport','remarks','gate_pass_vehicle','gate_pass_vehicle_police_no','gate_pass_vehicle_owner','direct_superior_id','gate_pass_planning_go_date','gate_pass_planning_return_date','gate_pass_planning_go_time','gate_pass_planning_return_time','gate_pass_necessity','submit_date','replace_by','replace_date','hr_by','hr_date','direct_by','direct_date','head_by','head_date','deputy_by','deputy_date','ceo_by','ceo_date','cby','cdate','eby','edate','full_approve_status','app_status','reject_status','reject_reason','note_status','publish_status','approval_code','created_by','updated_by','created_at','updated_at'];

    public static function approve_code_url(){
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, 50);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where(["full_approve_status"=>1, "publish_status"=>1]);
    }

    public static function date_correction($dates){
        $result = [];
        $period = CarbonPeriod::create($dates[0], $dates[count($dates)-1]);
        $result["status"] = true;
        $result["message"] = null;
        foreach ($period as $date) {
            if (!in_array($date->format('Y-m-d'), $dates)) {
                $result["status"] = false;
                if ($result["message"] != null) {
                    $result["message"] .= ", " . $date->format('j M Y');
                }else{
                    $result["message"] = $date->format('j M Y');
                }
            }
        }
        return $result;
    }

    public static function approve_request_leave($leave_id, $request, $wam_id = null){
        $data = [];
        $data["full_approve_status"] = 0;
        if ($wam_id != null) {
            $leave_approval_wa = LeaveApprovalWhatsapp::where(["wam_id" => $wam_id, "status"=>0]);
            if ($leave_approval_wa->count() == 1) {
                $leave_id = $leave_approval_wa->first()->leave_id;
                $approval_employee_id = $leave_approval_wa->first()->employee_id;
            }else{
                return false;
            }
        }else{
            $approval_employee_id = Dashboard::employee_id();
        }
        $leave_data = Leave::where(["id"=>$leave_id, "publish_status"=>1])->get();
        if (count($leave_data) == 0) {
            $success = false;
            $message = "Invalid URL";
        }else{
            $leave_approval_wa_lvl = null;
            $leave_approval_wa_lvl_next = null;
            $receiver_phone = [];
            if ($leave_data->first()->app_status == 0) {
                if ($wam_id == null && !Employee::check_access_or_delegation($leave_data->first()->replace_by)) {
                    $success = false;
                    $message = "Invalid User. Replacement User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["replace_date"] = Carbon::now()->toDateTimeString();
                $data["note_status"] = "Approved By Replace Man, waiting HR Approval";
                $leave_approval_wa_lvl = "replacement";
                $leave_approval_wa_lvl_next = "hr";

                //define receiver phone
                foreach (UserRole::where(["role_id"=>2])->get() as $key => $value) {
                    array_push($receiver_phone, ["phone"=>Dashboard::get_phone_number_or_delegation(Dashboard::employee_id_by_userid($value->user_id)), "employee_id"=>Dashboard::employee_id_by_userid($value->user_id)]);
                }
            }elseif ($leave_data->first()->app_status == 1) {
                if ($wam_id == null && !Dashboard::perm_check_menu([2, 4], $request->session()->get('userid'))) {
                    $success = false;
                    $message = "Invalid User. HR User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["hr_by"] = $approval_employee_id;
                $data["hr_date"] = Carbon::now()->toDateTimeString();
                $data["note_status"] = "Approved By HR, waiting Direct Superior Approval";
                $leave_approval_wa_lvl = "hr";
                $leave_approval_wa_lvl_next = "direct_superior";
                
                //define receiver phone
                array_push($receiver_phone, ["phone"=>Dashboard::get_phone_number_or_delegation($leave_data->first()->direct_superior_id), "employee_id"=>Dashboard::get_employee_id_or_delegation($leave_data->first()->direct_superior_id)]);
            }elseif ($leave_data->first()->app_status == 2) {
                if ($wam_id == null && !Employee::check_access_or_delegation($leave_data->first()->direct_superior_id)) {
                    $success = false;
                    $message = "Invalid User. Direct Superior User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["direct_by"] = $approval_employee_id;
                $data["direct_date"] = Carbon::now()->toDateTimeString();
                $data["note_status"] = "Approved By Direct Superior, waiting Plan Head Approval";
                $leave_approval_wa_lvl = "direct_superior";
                $leave_approval_wa_lvl_next = "plan_head";

                //define receiver phone
                foreach (UserRole::where(["role_id"=>12])->get() as $key => $value) {
                    array_push($receiver_phone, ["phone"=>Dashboard::get_phone_number_or_delegation(Dashboard::employee_id_by_userid($value->user_id)), "employee_id"=>Dashboard::get_employee_id_or_delegation(Dashboard::employee_id_by_userid($value->user_id))]);
                }
            }elseif ($leave_data->first()->app_status == 3) {
                if ($wam_id == null && !Dashboard::perm_check_menu([12, 4], $request->session()->get('userid'))) {
                    $success = false;
                    $message = "Invalid User. Plan Head User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["head_by"] = $approval_employee_id;
                $data["head_date"] = Carbon::now()->toDateTimeString();
                $leave_approval_wa_lvl = "plan_head";
                if (Employee::app_deputy_status($leave_data->first()->employee_id) == 1) {
                    $leave_approval_wa_lvl_next = "deputy";
                    //define receiver phone
                    $data["note_status"] = "Approved By Plan Head, waiting Deputy CEO Approval";
                    foreach (Employee::where(["position_id"=>25, "resign_date"=>null])->get() as $key => $value) {
                        array_push($receiver_phone, ["phone"=>Dashboard::get_phone_number_or_delegation($value->id), "employee_id"=>Dashboard::get_employee_id_or_delegation($value->id)]);
                    }
                }else{
                    $data["note_status"] = "Approved";
                    $data["full_approve_status"] = 1;
                }
            }elseif ($leave_data->first()->app_status == 4) {
                if ($wam_id == null && !Dashboard::perm_check_by_position([25, 4], $request->session()->get('userid'))) {
                    $success = false;
                    $message = "Invalid User. Deputy CEO User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["deputy_by"] = $approval_employee_id;
                $data["deputy_date"] = Carbon::now()->toDateTimeString();
                $leave_approval_wa_lvl = "deputy";
                if (Employee::app_ceo_status($leave_data->first()->employee_id) == 1) {
                    $leave_approval_wa_lvl_next = "ceo";
                    //define receiver phone
                    $data["note_status"] = "Approved By Deputy, waiting CEO Approval";
                    foreach (Employee::where(["position_id"=>9, "resign_date"=>null])->get() as $key => $value) {
                        array_push($receiver_phone, ["phone"=>Dashboard::get_phone_number_or_delegation($value->id), "employee_id"=>Dashboard::get_employee_id_or_delegation($value->id)]);
                    }
                }else{
                    $data["note_status"] = "Approved";
                    $data["full_approve_status"] = 1;
                }
            }elseif ($leave_data->first()->app_status == 5) {
                if ($wam_id == null && !Dashboard::perm_check_by_position([9, 4], $request->session()->get('userid'))) {
                    $success = false;
                    $message = "Invalid User. CEO User required";
                    return view("approval-notif", compact('message', 'success'));
                }
                $data["ceo_by"] = $approval_employee_id;
                $data["ceo_date"] = Carbon::now()->toDateTimeString();
                $leave_approval_wa_lvl = "ceo";
                $data["note_status"] = "Approved";
                $data["full_approve_status"] = 1;
            }elseif ($leave_data->first()->full_approve_status == 1) {
                if ($wam_id != null) {
                    return false;
                }
                $success = false;
                $message = "This document is aprroved";
                return view("approval-notif", compact('message', 'success'));
            }
            $data["app_status"] = $leave_data->first()->app_status + 1;
            if (Leave::where(["id"=>$leave_id])->update($data)) {
                $success = true;
                $message = "Approved";
                LeaveApprovalWhatsapp::where(["leave_id" => $leave_id, "level" => $leave_approval_wa_lvl, "action"=>null])->update(["status"=>2]);
                if ($wam_id != null) {
                    LeaveApprovalWhatsapp::where(["wam_id" => $wam_id])->update(["status"=>1, "action"=>"approve"]);
                }
                if ($data["full_approve_status"] == 1) {

                    $wa_template = Whatsapp::template_generator("approval_result", [
                            [
                                "type" => "body",
                                "parameters" => [["type"=>"text", "text"=>Dashboard::employee_name($leave_data->first()->employee_id)],["type"=>"text", "text"=>"Approved"],["type"=>"text", "text"=>"SLP"]]
                            ],
                            [
                                "type" => "button",
                                "sub_type" => "url",
                                "index" => "0",
                                "parameters" => [
                                    ["type"=>"text", "text"=>"leave-request-view/".$leave_data->first()->id."/".$leave_data->first()->employee_id."/".$leave_data->first()->slpdate]
                                ]
                            ]
                        ]);
                    $result_wa = Whatsapp::send_wa_api($wa_template, Dashboard::generate_phone_number_employee($leave_data->first()->employee_id));

                    GatePass::generate_auto_by_slp($leave_id, $request);
                }else{

                    for ($i=0; $i < count($receiver_phone); $i++) { 
                        $result_wa = Leave::send_wa_approval($receiver_phone[$i]["phone"], Dashboard::employee_name_dept($leave_data->first()->employee_id), $leave_id.'/'.$leave_data->first()->employee_id.'/'.$leave_data->first()->slpdate);
                        if (!isset($result_wa->messages[0]->id)) {
                            DB::table("notes")->create(["name"=>"wa_error_slp", "value"=>"{$leave_id}_pos={$leave_data->first()->app_status}"]);
                        }
                        LeaveApprovalWhatsapp::create(["leave_id"=>$leave_id, "employee_id"=>$receiver_phone[$i]["employee_id"], "level"=>$leave_approval_wa_lvl_next, "wam_id"=>$result_wa->messages[0]->id, "wa_number"=>$result_wa->contacts[0]->input]);
                    }
                }
            }else{
                $success = false;
                $message = "Something wrong";
            }
            if ($wam_id != null) {
                if (LeaveApprovalWhatsapp::where("wam_id", $wam_id)->count() == 1) {
                    $no_wa = LeaveApprovalWhatsapp::where("wam_id", $wam_id)->first()->wa_number;
                    if ($success) {
                        Whatsapp::send_wa_api_no_template("Approve successfull", $no_wa);
                    }else{
                        Whatsapp::send_wa_api_no_template("Approve Failed, unknown error", $no_wa);
                    }
                }
            }
        }
        return view("approval-notif", compact('message', 'success'));

    }

    public static function reject_request_leave($leave_id, $request, $wam_id = null){
        $data = [];
        if ($wam_id != null) {
            $leave_approval_wa = LeaveApprovalWhatsapp::where(["wam_id" => $wam_id, "status"=>0]);
            if ($leave_approval_wa->count() == 1) {
                $leave_id = $leave_approval_wa->first()->leave_id;
                $approval_employee_id = $leave_approval_wa->first()->employee_id;
            }else{
                return false;
            }
        }else{
            $approval_employee_id = Dashboard::employee_id();
        }
        $leave_data = Leave::where(["id"=>$leave_id, "publish_status"=>1])->get();
        if (count($leave_data) == 0) {
            $success = false;
            $message = "Invalid URL";
        }else{
            if($wam_id == null && $leave_data->first()->full_approve_status == 1 && $request->emergency_reject != null){
                $authorized = false;
                if (Employee::check_access_or_delegation($leave_data->first()->direct_superior_id)) {
                    $authorized = true;
                }
                if (Dashboard::employee_id() == $leave_data->first()->direct_superior_id) {
                    $authorized = true;
                }
                if ($authorized) {
                    $data["reject_status"] = 3;
                }else{
                    $success = false;
                    $message = "Invalid User.";
                    return view("approval-notif", compact('message', 'success'));
                }
                
            }elseif ($wam_id == null && $request->accidentally_approve != null) {
                if (Dashboard::employee_id() == $leave_data->first()->replace_by) {
                    $data["reject_status"] = 1;
                }elseif(Dashboard::perm_check_menu([2, 4], $request->session()->get('userid'))){
                    $data["reject_status"] = 2;
                }elseif(Dashboard::employee_id() == $leave_data->first()->direct_superior_id){
                    $data["reject_status"] = 3;
                }elseif(Dashboard::perm_check_menu([12, 4], $request->session()->get('userid'))){
                    $data["reject_status"] = 4;
                }else{
                    $success = false;
                    $message = "Invalid User.";
                    return view("approval-notif", compact('message', 'success'));
                }
            }else{
                if ($leave_data->first()->app_status == 0 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && Dashboard::employee_id() != $leave_data->first()->replace_by) {
                        $success = false;
                        $message = "Invalid User. Replacement User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 1;
                }elseif ($leave_data->first()->app_status == 1 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && !Dashboard::perm_check_menu([2, 4], $request->session()->get('userid'))) {
                        $success = false;
                        $message = "Invalid User. HR User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 2;
                }elseif ($leave_data->first()->app_status == 2 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && Dashboard::employee_id() != $leave_data->first()->direct_superior_id) {
                        $success = false;
                        $message = "Invalid User. Direct Superior User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 3;
                }elseif ($leave_data->first()->app_status == 3 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && !Dashboard::perm_check_menu([12, 4], $request->session()->get('userid'))) {
                        $success = false;
                        $message = "Invalid User. Plan Head User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 4;
                }elseif ($leave_data->first()->app_status == 4 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && !Dashboard::perm_check_by_position([25, 4], $request->session()->get('userid'))) {
                        $success = false;
                        $message = "Invalid User. Deputy CEO User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 5;
                }elseif ($leave_data->first()->app_status == 5 && $leave_data->first()->full_approve_status == 0) {
                    if ($wam_id == null && !Dashboard::perm_check_by_position([9, 4], $request->session()->get('userid'))) {
                        $success = false;
                        $message = "Invalid User. CEO User required";
                        return view("approval-notif", compact('message', 'success'));
                    }
                    $data["reject_status"] = 6;
                }elseif ($leave_data->first()->full_approve_status == 1) {
                    if ($wam_id != null) {
                        return false;
                    }
                    $success = false;
                    $message = "This document is aprroved";
                    return view("approval-notif", compact('message', 'success'));
                }
            }
            $data["note_status"] = "Rejected By ".Dashboard::employee_name($approval_employee_id);
            $data["reject_reason"] = ($request->reject_reason != null ? $request->reject_reason:"-");
            $data["app_status"] = 0;
            $data["total_taken"] = 0;
            $data["publish_status"] = 0;
            $data["total_day"] = 0;
            $data["approval_code"] = Leave::approve_code_url();

            $data["direct_by"] = null;
            $data["direct_date"] = null;
            $data["head_by"] = null;
            $data["head_date"] = null;
            $data["hr_by"] = null;
            $data["hr_date"] = null;
            $data["replace_date"] = null;
            $data["full_approve_status"] = 0;

            if (Leave::where(["id"=>$leave_id])->update($data)) {
                LeaveApprovalWhatsapp::where(["leave_id" => $leave_id, "action"=>null])->update(["status"=>2]);
                $leave_master_data = LeaveMaster::where(["employee_id"=>$leave_data->first()->employee_id])->get();
                if ($wam_id != null) {
                    LeaveApprovalWhatsapp::where(["wam_id" => $wam_id])->update(["status"=>1, "action"=>"reject"]);
                }
                LeaveMaster::where(["employee_id"=>$leave_data->first()->employee_id])->update([
                    "taken"=>$leave_master_data->first()->taken - $leave_data->first()->total_taken,
                    "balance"=>$leave_master_data->first()->balance + $leave_data->first()->total_taken,
                ]);
                $success = true;
                $message = "Reject Success";

                $wa_template = Whatsapp::template_generator("approval_result", [
                        [
                            "type" => "body",
                            "parameters" => [["type"=>"text", "text"=>Dashboard::employee_name($leave_data->first()->employee_id)],["type"=>"text", "text"=>"Rejected"],["type"=>"text", "text"=>"SLP"]]
                        ],
                        [
                            "type" => "button",
                            "sub_type" => "url",
                            "index" => "0",
                            "parameters" => [
                                ["type"=>"text", "text"=>"leave-request-view/".$leave_data->first()->id."/".$leave_data->first()->employee_id."/".$leave_data->first()->slpdate]
                            ]
                        ]
                    ]);
                $result_wa = Whatsapp::send_wa_api($wa_template, Dashboard::generate_phone_number_employee($leave_data->first()->employee_id));
            }else{
                $success = false;
                $message = "Something wrong";
            }
            if ($wam_id != null) {
                if (LeaveApprovalWhatsapp::where("wam_id", $wam_id)->count() == 1) {
                    $no_wa = LeaveApprovalWhatsapp::where("wam_id", $wam_id)->first()->wa_number;
                    if ($success) {
                        Whatsapp::send_wa_api_no_template("Reject successfull", $no_wa);
                    }else{
                        Whatsapp::send_wa_api_no_template("Reject Failed, unknown error", $no_wa);
                    }
                }
            }
        }
        if ($wam_id != null) {
            return $success;
        }
        return view("approval-notif", compact('message', 'success'));
    }

    public static function send_wa_approval($no, $employee_name, $url){
        $wa_template = Whatsapp::template_generator("slp_request", [
            [
                "type" => "body",
                "parameters" => [["type"=>"text", "text"=>$employee_name]]
            ],
            [
                "type" => "button",
                "sub_type" => "url",
                "index" => "0",
                "parameters" => [
                    ["type"=>"text", "text"=>$url]
                ]
            ]
        ]);
        return Whatsapp::send_wa_api($wa_template, $no);
    }

    public static function get_approval_list($request){
        $where_in_employee_id = [Dashboard::employee_id()];
        $cek_delegation = Delegation::cek_delegation_employee_id_to_me(Carbon::now()->format("Y-m-d"));
        for ($i=0; $i < count($cek_delegation); $i++) { 
            if(!in_array($cek_delegation[$i], $where_in_employee_id)){
                array_push($where_in_employee_id, $cek_delegation[$i]);
            }
        }
        $data = LeaveApprovalWhatsapp::select("leave_approval_whatsapp.leave_id as leave_id", "leaves.note_status as note_status", "leaves.slpdate as slp_date", "leaves.employee_id as employee_id_request_by")
        ->join("leaves", "leaves.id", "leave_approval_whatsapp.leave_id")
        ->where(["leave_approval_whatsapp.status"=>0])
        ->whereIn("leave_approval_whatsapp.employee_id", $where_in_employee_id)
        ->distinct('leave_approval_whatsapp.leave_id')
        ->get();
        
        return $data;
    }
}
