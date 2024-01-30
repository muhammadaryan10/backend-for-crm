<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emp_login extends Model
{
    function login_id()
    {
        return $this->hasMany('App\Models\Emp_logout', 'login_id', 'id');
    }
    public function get_emp_login()
    {
        return $this->belongsTo('App\Models\Employee', 'emp_id', 'emp_id');
    }
    use HasFactory;
    protected $table='workers_login';
    // protected $primaryKey = 'emp_id';
    protected $fillable = [
        'emp_id',
        'Emp_name',
        'login_id',
        'status'
    ];
}
