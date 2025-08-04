<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameUser extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'users';

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['uuid', 'name', 'auth_id', 'ip', 'nationality', 'blacklist'];

    // Example relation to player medals
    public function medals()
    {
        return $this->hasMany(PlayerMedal::class, 'user_uuid', 'uuid');
    }

    public function times()
    {
        return $this->hasMany(Time::class, 'user_uuid', 'uuid');
    }
}

?>