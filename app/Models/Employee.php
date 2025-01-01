<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Delegation;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $fillable = ['id','nik','fullname','gender','marital_status','kebangsaan','religion','birth_date','birth_place','address1','adderss2','telpno','hp','office_email','private_email','ktp','sim','kk','direct_superior_id','company_id','department_id','division_id','position_id','bpjs_kes_no','bpjs_tk_no','contact_name','contact_no','tax_status','npwp_no','accno','accname','accbank','ktp_pic','photo_pic','join_date','resign_date','status_resign','contract_status','status','deleted_at','deleted_by','shift','shift_type','shift_group_id','canteen','slp','room','emp_type','grade','is_admin','idcard','group_id','delegation_id','app_deputy','app_ceo','created_at','created_by','updated_at','updated_by'];

    public function scopeNonResign(Builder $query)
    {
        return $query->where(["resign_date"=>null, "status_resign"=>null]);
    }
    public function scopeIsAdmin(Builder $query)
    {
        return $query->where(["is_admin"=>1]);
    }
    public function scopeIsNotAdmin(Builder $query)
    {
        return $query->where(["is_admin"=>0]);
    }
    

    public static function employee_name_dept($id){
        try {
            $department = (Department::where('id',Employee::where('id',$id)->get()->first()->department_id)->count() == 1 ? Department::where('id',Employee::where('id',$id)->get()->first()->department_id)->get()->first()->name:"DEPT NULL");
        } catch (\Exception $e) {
            $department = "DEPT ERROR";
        }
        try {
            $fullname = Employee::where('id',$id)->get()->first()->fullname;
        } catch (\Exception $e) {
            $fullname = "NAME ERROR";
        }
        return $fullname . ' | ' . $department ;
    }

    public static function phone_number_format_replace($phone){
        $phone = ltrim($phone, "‬");
        $phone = ltrim($phone, "‪");
        $phone = ltrim($phone, " ");
        $phone = ltrim($phone, "+62");
        $phone = ltrim($phone, "62");
        $phone = ltrim($phone, "+");
        $phone = ltrim($phone, "0");
        $phone = ltrim($phone, " ");
        $phone = str_replace("-","", $phone);
        $phone = str_replace(" ","", $phone);
        return $phone;
    }
}
