<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleApplicationAccessView extends Model
{
    protected $table = 'v_role_application_access_view';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

}