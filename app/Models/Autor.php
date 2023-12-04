<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autor extends Model
{
    use HasFactory;
    protected $table = 'autors';
    protected $fillable = ['nombre', 'pestudio_id'];

    public function pestudio()
    {
        return $this->belongsTo(Pestudio::class, 'pestudio_id');
    }
    
    public function trabajosDeAplicacion()
    {
        return $this->belongsToMany(Taplicacion::class, 'trabajo_autors', 'autor_id', 'trabajo_id')
                    ->withTimestamps();
    }
}
