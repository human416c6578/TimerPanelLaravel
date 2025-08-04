<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayedTime extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'played_time';

    protected $primaryKey = ['auth_id', 'server'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['auth_id', 'server', 'name', 'time_played', 'first_seen', 'last_seen'];

    // Override getKey for composite PK (optional, advanced)
    public function getKeyName()
    {
        return ['auth_id', 'server'];
    }
}

?>