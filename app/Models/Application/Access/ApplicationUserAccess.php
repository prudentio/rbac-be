<?php

namespace App\Models\Application\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Application;
use App\Models\User;

class ApplicationUserAccess extends BaseModel
{
    use HasFactory;

    protected $table = 'application_user_access';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'application_id',
        'is_denied',
    ];

    protected $casts = [
        'is_denied' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function application()
    {
        return $this->belongsTo(
            Application::class,
            'application_id',
            'id'
        );
    }
}