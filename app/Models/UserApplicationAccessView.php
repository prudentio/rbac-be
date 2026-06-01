<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApplicationAccessView extends Model
{
    protected $table = 'v_user_application_access';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}