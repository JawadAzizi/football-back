<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $fillable = ['name', 'phone', 'email', 'email_verified_at', 'password', 'email', 'token', 'user_id', 'user_agent', 'payload', 'last_activity'];
}