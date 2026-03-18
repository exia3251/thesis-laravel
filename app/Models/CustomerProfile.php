<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $primaryKey = 'profile_id';
    protected $fillable = ['user_id', 'phone', 'email', 'address'];
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
}