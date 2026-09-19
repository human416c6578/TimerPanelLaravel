<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ranking extends Model
{
    protected $connection = 'game_mysql';

    protected $table = 'ranking';

    protected $primaryKey = 'user_uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_uuid', 'score', 'bronze', 'silver', 'gold'];

    public function user()
    {
        // The game's own player, not the panel's admin User this used to point at.
        return $this->belongsTo(GameUser::class, 'user_uuid', 'uuid');
    }
}
