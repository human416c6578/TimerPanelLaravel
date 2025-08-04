<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'categories';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'name', 'fps', 'gravity', 'speed', 'ground_speed', 'start_speed', 'speedrun', 'auto_bhop', 'hook'
    ];
}

?>