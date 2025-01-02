<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\CanteenTapLog;
use App\Models\EmployeeCanteenCard;

class EmployeeCanteenTransaction extends Model
{
    use HasFactory;

    protected $table = 'employee_canteen_transactions';
    protected $fillable = ['id','canteen_id','card_code','type','time_category','employee_id', 'created_at', 'updated_at'];
    public static function tap_request($request){
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        $canteen_id = ["canteen_1"=>1, "canteen_2"=>2, "canteen_3"=>3];
        $last_5_trx_data = EmployeeCanteenTransaction::where("canteen_id",$canteen_id[$request->canteen_name])->orderBy("id", "desc")->limit(5)->get();
        $last_5_trx = [];
        foreach ($last_5_trx_data as $key => $value) {
            $msg = Employee::employee_name_dept($value->employee_id) . " Date : " . $value->created_at;
            array_push($last_5_trx, $msg);
        }
        $time_category = ["", "Breakfast", "Lunch", "Dinner"];
        if ((int)$request->time_category > 3) {
            return;
        }
        $check_exist = Employee::where("idcard",$request->card_id);
        if ($check_exist->count() == 0) {
            $msg = "Request Failed\nInvalid Card.\nCARD CODE : {$request->card_id}\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg, "created_at"=>Carbon::now()->format("Y-m-d H:i:s"), "updated_at"=>null]);
            return json_encode(["last_trx"=>json_encode($last_5_trx),"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>"???"]);
        }
        $employee_name = $check_exist->first()->fullname;

        $check_card = EmployeeCanteenCard::where("employee_id", $check_exist->first()->id);
        if ($check_card->count() == 0) {
            $msg = "Request Failed\nThis card is not register yet.\nCARD CODE : {$request->card_id}\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
            return json_encode(["last_trx"=>json_encode($last_5_trx),"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>"???"]);
        }

        if ($check_card->first()->status == 0) {
            $msg = "Request Failed\nThis card is not activated, contact your support.\nCARD CODE : {$request->card_id}\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
            return json_encode(["last_trx"=>json_encode($last_5_trx),"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>$employee_name]);
        }
        $access_time = ["", "access_breakfast", "access_lunch", "access_dinner"];
        $employee_pic = $check_exist->first()->photo_pic;
        if ($request->time_category == "") {
            $request->time_category = "0";
        }
        if ($request->time_category == "0") {
            if (Carbon::now() >= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "05:30") && Carbon::now() <= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "08:00")) {
                $request->time_category = "1";
            }elseif (Carbon::now() >= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "11:00") && Carbon::now() <= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "14:00")) {
                $request->time_category = "2";
            }elseif (Carbon::now() >= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "16:30") && Carbon::now() <= Carbon::createFromFormat("Y-m-d H:i", Carbon::now()->format("Y-m-d") . "20:00")) {
                $request->time_category = "3";
            }else{
                $msg = "{$access_time[$request->time_category]}\nReason : You Tapped outside of meal times. Please press the button to select claim daily meal Slot\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
                CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
                return json_encode(["last_trx"=>json_encode($last_5_trx),"photo"=>$employee_pic,"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>$employee_name]);
            }
        }
        if($check_card->where([$access_time[$request->time_category] => 1])->count() == 0)
        {
            $msg = "Request Failed\nReason : You dont have access daily meal (".strtoupper($time_category[$request->time_category]).").\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
        	return json_encode(["last_trx"=>json_encode($last_5_trx),"photo"=>$employee_pic,
                                "msg"=>$msg,
                                "card_id"=>$request->card_id,
                                "time_pause" => 10000,
                                "status" => 0,
                                "idcard" => $request->card_id,
                                "employee_name" => $employee_name]);
        }
        $check_canteen_trx = EmployeeCanteenTransaction::where(["employee_id"=>$check_exist->first()->id, "time_category"=>$request->time_category])->whereBetween("created_at",[Carbon::now()->startOfDay()->format("Y-m-d H:i:s"), Carbon::now()->endOfDay()->format("Y-m-d H:i:s")]);
        $check_canteen_trx_all = EmployeeCanteenTransaction::where(["employee_id"=>$check_exist->first()->id])->whereBetween("created_at",[Carbon::now()->startOfDay()->format("Y-m-d H:i:s"), Carbon::now()->endOfDay()->format("Y-m-d H:i:s")]);

        $tot_trx = $check_canteen_trx->count();
        if ($tot_trx == 1) {
            $msg = "Request Failed\nReason : You have used your card for {$time_category[$request->time_category]}.\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
            return json_encode(["last_trx"=>json_encode($last_5_trx),"photo"=>$employee_pic,"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>$employee_name]);
        }
        $check_leave = LeaveDetail::where(["employee_id"=>$check_exist->first()->id, ["date_detail","LIKE","%".Carbon::now()->startOfDay()->format("Y-m-d")."%"]]);
        if ($check_leave->count() != 0) {
            $msg = "Request Failed\nReason : Your card cannot be used because you are recorded as being on leave.\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
            CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>0, "description"=>$msg]);
            return json_encode(["last_trx"=>json_encode($last_5_trx),"photo"=>$employee_pic,"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>10000,"status"=>0, "idcard"=>$request->card_id, "employee_name"=>$employee_name]);
        }
        EmployeeCanteenTransaction::insert([
            "canteen_id" => $canteen_id[$request->canteen_name],
            "card_code" => $request->card_id,
            "employee_id" => $check_exist->first()->id,
            "time_category" => $request->time_category,
        ]);
        $msg = "Request Success\nEnjoy your {$time_category[$request->time_category]}\nCanteen : Canteen {$canteen_id[$request->canteen_name]}";
        CanteenTapLog::create(["card_code"=>$request->card_id, "canteen_name"=>$canteen_id[$request->canteen_name], "status"=>1, "description"=>$msg]);
        return json_encode(["last_trx"=>json_encode($last_5_trx),"photo"=>$employee_pic,"msg"=>$msg, "card_id"=>$request->card_id, "name"=>"Name respon", "time_pause"=>5000,"status"=>1, "idcard"=>$request->card_id, "employee_name"=>$employee_name]);
    }
}
