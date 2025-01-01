<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCanteenTransaction;
use Illuminate\Http\Request;
use App\Events\CardTapBroadcastEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class MainController extends Controller
{
    public function get_data_tap_from_device(Request $request){
        $response = EmployeeCanteenTransaction::tap_request($request);
        $response = json_decode($response);
        $response->canteen_name = str_replace("canteen_", "", $request->canteen_name);
        CardTapBroadcastEvent::dispatch($response);
        return $response;
    }
    public function get_last_5_trx(Request $request){
        $last_5_trx_data = EmployeeCanteenTransaction::where("canteen_id",$request->canteen_name)->orderBy("id", "desc")->limit(5)->get();
        $last_5_trx = [];
        foreach ($last_5_trx_data as $key => $value) {
            $msg = Employee::employee_name_dept($value->employee_id) . " Date : " . $value->created_at;
            array_push($last_5_trx, $msg);
        }
        return json_encode($last_5_trx);
    }
}
