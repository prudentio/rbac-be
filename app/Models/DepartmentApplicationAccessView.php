<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentApplicationAccessView extends Model
{
    protected $table = 'v_department_application_access';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}