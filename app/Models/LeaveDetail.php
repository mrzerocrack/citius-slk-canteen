<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveDetail extends Model
{
    use HasFactory;

    protected $table = 'leave_details';
    protected $fillable = ['id','employee_id','leave_id','type_id','date1','date2','date_detail','notes','created_by','updated_by','created_at','updated_at'];
}
