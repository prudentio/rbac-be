<?php

namespace App\Models\Application\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Application;
use App\Models\Department;


class ApplicationDepartmentAccess extends BaseModel
{
    use HasFactory;

    protected $table = 'application_department_access';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'department_id',
    ];

    public function application()
    {
        return $this->belongsTo(
            Application::class,
            'application_id',
            'id'
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'department_id',
            'id'
        );
    }
}