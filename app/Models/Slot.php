<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Slot extends Model
{
    use HasFactory;
    protected $table = 'slots';
    protected $fillable = ['date', 'time', 'status', 'salon_id'];
}