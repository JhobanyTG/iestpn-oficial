<?php

namespace App\Http\Controllers;

use App\Models\Taplicacion;
use App\Models\Autor;
use App\Models\Pestudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Exception;

class PublicController extends Controller
{

    public function index(Request $request)
    {
        $trabajoAplicacion = Taplicacion::all();

        $query = Taplicacion::with('autores.pestudio');

            $searchTerm = $request->input('q');
            $fecha = $request->input('fecha');
            $filtroAnio = $request->input('anio');
            $selectedPestudios = $request->input('pestudio', []);
            $selectedTipos = $request->input('tipo', []);

            if ($searchTerm || $fecha || $filtroAnio || !empty($selectedPestudios) || !empty($selectedTipos)) {
                if (str_contains($searchTerm, ';')) {
                    $autoresArray = explode(';', $searchTerm);

                    $query->whereHas('autores', function ($query) use ($autoresArray) {
                        $query->whereIn('nombre', $autoresArray);
                    });
                } else {
                    $query->where(function ($query) use ($searchTerm) {
                        $query->where('titulo', 'like', '%' . $searchTerm . '%')
                            ->orWhereHas('autores', function ($query) use ($searchTerm) {
                                $query->where('nombre', 'like', '%' . $searchTerm . '%');
                            })
                            ->orWhereHas('autores.pestudio', function ($query) use ($searchTerm) {
                                $query->where('nombre', 'like', '%' . $searchTerm . '%');
                            })
                            ->orWhere('tipo', 'like', '%' . $searchTerm . '%')
                            ->orWhere('resumen', 'like', '%' . $searchTerm . '%');
                    });
                }

                if ($fecha) {
                    $query->whereDate('created_at', $fecha);
                }
                if ($filtroAnio) {
                    $query->whereYear('created_at', $filtroAnio);
                }

                if (!empty($selectedPestudios)) {
                    $query->whereHas('autores.pestudio', function ($query) use ($selectedPestudios) {
                        $query->whereIn('nombre', $selectedPestudios);
                    });
                }

                if (!empty($selectedTipos)) {
                    $query->whereIn('tipo', $selectedTipos);
                }


            } else {
                $query->get();
            }
            $query->orderByDesc('created_at');
            $trabajoAplicacion = $query->paginate(5);
            $trabajoAplicacion->appends(['q' => $searchTerm, 'fecha' => $fecha, 'anio' => $filtroAnio, 'tipo' => $selectedTipos, 'pestudio' => $selectedPestudios]);
            foreach ($trabajoAplicacion as $trabajo) {
                $autores = $trabajo->autores;
                $programasDeEstudio = $autores->pluck('pestudio')->filter(function ($value) {
                    return !is_null($value);
                });

                $programaEstudiosMasComun = 'Sin programa de estudios';

                if ($programasDeEstudio->count() > 0) {
                    $programasCount = $programasDeEstudio->countBy('id')->sortDesc();
                    $idMasComun = $programasCount->keys()->first();
                    $programaEstudiosMasComun = $programasDeEstudio->where('id', $idMasComun)->first()->nombre;
                }

                $trabajo->programaEstudiosMasComun = $programaEstudiosMasComun;
            }
            $availableYears = Taplicacion::distinct()
                ->orderByDesc('created_at')
                ->pluck('created_at')
                ->map(function ($date) {
                    return $date->format('Y');
                })
                ->unique();

            return view('publics.index', compact('trabajoAplicacion', 'searchTerm', 'fecha', 'availableYears', 'filtroAnio', 'selectedPestudios', 'selectedTipos'));
    }
    public function create()
    {
        //
    }
    public function store(Request $request)
    {
        //
    }
    public function show($id)
    {
        $taplicacion = Taplicacion::findOrFail($id);
        $taplicacion->load('autores.pestudio');
        $programasCount = $taplicacion->autores->groupBy('pestudio_id')->map->count();
        $mostCommonProgramaEstudiosId = $programasCount->sortDesc()->keys()->first();
        $mostCommonProgramaEstudios = $taplicacion->autores->firstWhere('pestudio_id', $mostCommonProgramaEstudiosId)->pestudio;

        return view('publics.show', compact('taplicacion', 'mostCommonProgramaEstudios'));
    }

    public function edit(Taplicacion $taplicacion)
    {
        //
    }

    public function update(Request $request, Taplicacion $taplicacion)
    {
        //
    }

    public function destroy(Taplicacion $taplicacion)
    {
        //
    }
}
