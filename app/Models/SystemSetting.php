<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SystemSetting extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'system_settings';

    protected $fillable = [
        'key',
        'raw_value',
        'encrypted',
        'group',
        'label',
        'description',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
    ];
}
