<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emp_logout extends Model
{
    use HasFactory;
    public function get_emp_logout()
    {
        return $this->belongsTo('App\Models\Employee', 'emp_id', 'emp_id');
    }
    function login_id()
    {
        return $this->belongsTo('App\Models\Emp_login', 'login_id', 'id');
    }
    use HasFactory;
    protected $table='workers_checkout';
    // protected $primaryKey = 'emp_id';
    protected $fillable = [
        'emp_id',
        'Emp_name',
        'logout_id',
        'login_id',
        'status'
    ];
}
