<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\ApplicationCategory;
use App\Models\Application\Access\ApplicationDepartmentAccess;
use App\Models\Application\Access\ApplicationRoleAccess;
use App\Models\Application\Access\ApplicationUserAccess;
use App\Models\User;

class Application extends BaseModel
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'name',
        'description',
        'url',
        'icon',
        'category_id',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(
            ApplicationCategory::class,
            'category_id',
            'id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by',
            'id'
        );
    }

    public function departmentAccess()
    {
        return $this->hasMany(
            ApplicationDepartmentAccess::class,
            'application_id',
            'id'
        );
    }

    public function roleAccess()
    {
        return $this->hasMany(
            ApplicationRoleAccess::class,
            'application_id',
            'id'
        );
    }

    public function userAccess()
    {
        return $this->hasMany(
            ApplicationUserAccess::class,
            'application_id',
            'id'
        );
    }
}