<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taplicacion extends Model
{
    use HasFactory;
   
    protected $table = 'taplicacions';
    protected $fillable = ['titulo', 'resumen', 'archivo', 'tipo'];

    public function autores()
    {
        return $this->belongsToMany(Autor::class, 'trabajo_autors', 'trabajo_id', 'autor_id')
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getInterdisciplinarioAttribute()
    {
    $autores = $this->autores;

    if ($autores->count() >= 2) {
        $programasDeEstudio = $autores->pluck('pestudio_id')->unique();
        return $programasDeEstudio->count() > 1;
    }

    return false;
    }
}
