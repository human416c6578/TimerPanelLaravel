<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'times';

    // Can't retrieve the record without providing the user_uuid
    //protected $primaryKey = ['user_uuid', 'map_uuid', 'category_id'];  // composite key
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_uuid', 'map_uuid', 'category_id', 'time', 'record_date', 
        'start_speed', 'jumps', 'strafes', 'sync', 'overlaps', 'overlaps_sd'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function map()
    {
        return $this->belongsTo(Map::class, 'map_uuid', 'uuid');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}

?>