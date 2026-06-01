<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Application\Application;

class ApplicationCategory extends BaseModel
{
    use HasFactory;

    protected $table = 'application_categories';

    protected $fillable = [
        'name',
    ];

    public function applications()
    {
        return $this->hasMany(
            Application::class,
            'category_id',
            'id'
        );
    }
}