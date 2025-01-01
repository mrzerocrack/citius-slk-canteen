<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use DB;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';
    protected $fillable = ['division_id','name','notes','created_by','updated_by'];

    public function insert($request){
        return DB::table($this->table)->insert(["name"=>$request->input("name"),"notes"=>$request->input("notes")]);
    }

    public function my_dept($request){
        $employee_id = DB::table("users")->where(['id'=>$request->session()->get("userid")])->first()->employee_id;
        $cek_employe = DB::table("employees")->where(['id'=>$employee_id]);
        return DB::table($this->table)->where("id",$cek_employe->first()->department_id)->first()->name;
    }
}
