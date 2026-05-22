<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salon extends Model
{
    use HasFactory;
    protected $table = 'salons';
    protected $fillable = ['name', 'address', 'phone', 'status', 'user_id'];
}