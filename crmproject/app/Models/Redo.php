<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redo extends Model
{
    public function getcountrycode()
    {
        return $this->belongsTo('App\Models\User', 'client_id', 'id');
    }
    use HasFactory;
    protected $table='redo';

}
