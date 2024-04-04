<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['client_id'];
}
