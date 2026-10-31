<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    // VULNERABILITY: Mass Assignment — attacker can set: role='admin', max_borrow=999
    protected $guarded = [];
}
