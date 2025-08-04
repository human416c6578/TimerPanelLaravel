<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayedTimeInfo extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'played_time_info';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['auth_id', 'server', 'date_join', 'date_left'];
}

?>