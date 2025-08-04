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
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}

?>