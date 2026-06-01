<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Access\ApplicationRoleAccess;
use App\Models\User;

class Role extends BaseModel
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'is_super_admin',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function applicationAccess()
    {
        return $this->hasMany(ApplicationRoleAccess::class);
    }
}