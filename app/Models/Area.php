<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'rest_areas';
use HasFactory;

    protected $fillable = ['name'];

    // RelaciÃƒÂ³n: Un ÃƒÂ¡rea tiene muchas mesas
    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}
