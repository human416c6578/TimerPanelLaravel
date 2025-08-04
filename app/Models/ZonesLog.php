<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonesLog extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'zones_logs';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_uuid', 'map_uuid', 'updated_at', 'previous_values', 'new_values'];

    protected $casts = [
        'previous_values' => 'array',
        'new_values' => 'array',
    ];
}

?>