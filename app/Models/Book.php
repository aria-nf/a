<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    // VULNERABILITY: Mass Assignment — attacker can set: is_rare=1, restricted=0, fine_rate=0
    protected $fillable = [];
}
