<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerMedals extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'player_medals';

    protected $primaryKey = ['user_uuid', 'map_uuid'];  // composite key, see note
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_uuid', 'map_uuid', 'bronze', 'silver', 'gold'];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function map()
    {
        return $this->belongsTo(Map::class, 'map_uuid', 'uuid');
    }
}

?>
