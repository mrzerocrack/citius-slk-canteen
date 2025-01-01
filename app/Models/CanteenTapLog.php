<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanteenTapLog extends Model
{
    use HasFactory;

    protected $table = 'canteen_tap_logs';
    protected $fillable = ['card_code','canteen_name','status','description','created_at','updated_at'];
}
