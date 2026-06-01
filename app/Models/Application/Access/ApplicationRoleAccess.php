<?php

namespace App\Models\Application\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Application;
use App\Models\Role;

class ApplicationRoleAccess extends BaseModel
{
    use HasFactory;

    protected $table = 'application_role_access';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'role_id',
    ];

    public function application()
    {
        return $this->belongsTo(
            Application::class,
            'application_id',
            'id'
        );
    }

    public function role()
    {
        return $this->belongsTo(
            Role::class,
            'role_id',
            'id'
        );
    }

}