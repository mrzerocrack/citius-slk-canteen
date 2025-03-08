<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCanteenCard extends Model
{
    use HasFactory;

    protected $table = 'employee_canteen_card_masters';
    protected $fillable = ['employee_id','card_code','status','access_breakfast','access_lunch','access_dinner','access_suhoor','notes','created_by','updated_by','created_at','updated_at'];
}
