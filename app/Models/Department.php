<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Access\ApplicationDepartmentAccess;
use App\Models\User;

class Department extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->hasMany(
            User::class,
            'department_id',
            'id'
        );
    }

    public function applicationAccess()
    {
        return $this->hasMany(
            ApplicationDepartmentAccess::class,
            'department_id',
            'id'
        );
    }
}