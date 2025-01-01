<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Delegation extends Model
{
    use HasFactory;

    protected $table = 'delegations';
    protected $fillable = ['id','employee_id', 'to_employee_id','date','notes','created_at','created_by','updated_at','updated_by'];

    public static function cek_delegation($date, $employee_id)
    {
        $delegation = self::where('date', $date)->where('employee_id', $employee_id)->count();

        return $delegation != 0 ? true : false;
    }

    public static function get_list_delegation_employee_data($employee_id){
        $delegation = self::where('employee_id', $employee_id)->orderBy("date", "asc")->where("date",">=",Carbon::now()->format("Y-m-d"))->get();
        return $delegation;
    }

    public static function get_delegation($date, $employee_id){
        $delegation = self::where('employee_id', $employee_id)->where("date",$date)->first()->to_employee_id;
        return $delegation;
    }

    public static function cek_delegation_to_me($date, $employee_id){
        $delegation = self::where('date', $date)->where('employee_id', $employee_id);
        if ($delegation->count() == 0) {
            return false;
        }
        if ($delegation->first()->to_employee_id == Dashboard::employee_id()) {
            return true;
        }else{
            return false;
        }
    }

    public static function cek_delegation_role_to_me($date){
        $result = [];
        $delegation = self::where('date', $date)->where('to_employee_id', Dashboard::employee_id());
        if ($delegation->count() == 0) {
            return $result;
        }
        foreach ($delegation->get() as $key => $value) {
            foreach (UserRole::where("user_id", Dashboard::employee_userid_by_employee_id($value->employee_id))->get() as $key2 => $value2) {
                if(!in_array($value2->role_id, $result)){
                    array_push($result, $value2->role_id);
                }
            }
        }
        return $result;
    }

    public static function cek_delegation_position_to_me($date){
        $result = [];
        $delegation = self::where('date', $date)->where('to_employee_id', Dashboard::employee_id());
        if ($delegation->count() == 0) {
            return $result;
        }
        foreach ($delegation->get() as $key => $value) {
            $position = Employee::where("id", $value->employee_id)->first()->position_id;
            if(!in_array($position, $result)){
                array_push($result, $position);
            }
        }
        return $result;
    }

    public static function cek_delegation_employee_id_to_me($date){
        $result = [];
        $delegation = self::where('date', $date)->where('to_employee_id', Dashboard::employee_id());
        if ($delegation->count() == 0) {
            return $result;
        }
        foreach ($delegation->get() as $key => $value) {
            if(!in_array($value->employee_id, $result)){
                array_push($result, $value->employee_id);
            }
        }
        return $result;
    }
}
