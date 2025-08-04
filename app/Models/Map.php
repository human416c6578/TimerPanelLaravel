<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $connection = 'game_mysql';
    protected $table = 'maps';

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['uuid', 'name'];
}

?>