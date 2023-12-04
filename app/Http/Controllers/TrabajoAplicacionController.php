<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use App\Models\Pestudio;
use App\Models\TrabajoAutor;
use Illuminate\Support\Facades\Log;
use App\Models\Taplicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;
use Exception;

class TrabajoAplicacionController extends Controller
{
    public function index(Request $request)
    {

        if ($user = auth()->user()) {
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

                return view('trabajoAplicacion.index', compact('trabajoAplicacion', 'searchTerm', 'fecha', 'availableYears', 'filtroAnio', 'selectedPestudios', 'selectedTipos'));
            } else {
            return redirect()->to('/');
        }
    }

    public function create()
    {
        if (auth()->user()) {
            $pestudios = Pestudio::all();
            return view('trabajoAplicacion.create', compact('pestudios'));
        } else {
            return redirect()->to('/');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'titulo' => 'required',
                'resumen' => 'required',
                'archivo' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10000',
                    Rule::unique('taplicacions', 'archivo'),
                ],
                'autors' => 'nullable|array|min:1',
                'autors.*' => 'required_with:autors|string|max:255',
                'pestudio_id' => 'nullable|array|min:1',
                'pestudio_id.*' => 'required_with:pestudio_id|numeric|exists:pestudios,id',
            ]);

            $archivo = $request->file('archivo');
            $archivoNombre = $archivo->getClientOriginalName();
            $archivoRuta = $archivo->storeAs('archivos', $archivoNombre, 'public');
            $archivoExiste = Taplicacion::where('archivo', $archivoRuta)->exists();

            if ($archivoExiste) {
                return redirect()->route('trabajoAplicacion.create')->withErrors(['archivo' => 'El archivo con ese nombre ya existe. Por favor, elige otro archivo diferente.'])->withInput();
            }

            foreach ($request->autors as $key => $autorNombre) {
                $autorExistente = Autor::where('nombre', $autorNombre)
                    ->where('pestudio_id', '!=', $request->pestudio_id[$key])
                    ->first();

                if ($autorExistente) {
                    return redirect()->route('trabajoAplicacion.create')
                        ->withErrors(['autors.' . $key => "El autor '$autorNombre' ya pertenece a otro programa de estudios."])
                        ->withInput();
                }
            }

            $trabajoAplicacion = new Taplicacion();
            $trabajoAplicacion->titulo = $request->titulo;
            $trabajoAplicacion->resumen = $request->resumen;
            $trabajoAplicacion->archivo = $archivoRuta;
            $trabajoAplicacion->user_id = auth()->user()->id;

            if (count($request->autors) >= 2) {
                $programasDeEstudio = collect($request->pestudio_id)->unique();
                $trabajoAplicacion->tipo = $programasDeEstudio->count() > 1 ? 'Interdisciplinario' : 'Normal';
            } else {
                $trabajoAplicacion->tipo = 'Normal';
            }

            $trabajoAplicacion->save();

            foreach ($request->autors as $key => $autorNombre) {
                $autor = Autor::firstOrCreate([
                    'nombre' => $autorNombre,
                    'pestudio_id' => $request->pestudio_id[$key],
                ]);

                $trabajoAplicacion->autores()->attach($autor->id);
            }

            Session::flash('success', 'El trabajo de aplicación ha sido creado exitosamente.');
            return redirect()->route('trabajoAplicacion.index');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->route('trabajoAplicacion.index')
                ->with('error', 'Ha ocurrido un error en el servidor. Por favor, inténtalo de nuevo más tarde.');
        }
    }

    public function show($id)
    {
        $taplicacion = Taplicacion::findOrFail($id);
        $taplicacion->load('autores.pestudio');
        $programasCount = $taplicacion->autores->groupBy('pestudio_id')->map->count();
        $mostCommonProgramaEstudiosId = $programasCount->sortDesc()->keys()->first();
        $mostCommonProgramaEstudios = $taplicacion->autores->firstWhere('pestudio_id', $mostCommonProgramaEstudiosId)->pestudio;

        return view('trabajoAplicacion.show', compact('taplicacion', 'mostCommonProgramaEstudios'));
    }
    public function edit($id)
    {
        if(auth()->user()){
            $taplicacion = TAplicacion::findOrFail($id);
            $pestudios = Pestudio::all();
            return view('trabajoAplicacion.edit', compact('taplicacion', 'pestudios'));
        } else{
            return redirect()->to('/');
        }
    }

    public function update(Request $request, $id)
{
    $taplicacion = TAplicacion::findOrFail($id);

    $request->validate([
        'titulo' => 'required|max:255',
        'resumen' => 'required',
        'archivo' => [
            'nullable',
            'file',
            'mimes:pdf',
            'max:10000',
            Rule::unique('taplicacions')->ignore($taplicacion->id),
        ],
        'autors' => 'required|array|min:1',
        'autors.*' => 'required|string|max:255',
        'pestudio_id' => 'required|array|min:1',
        'pestudio_id.*' => 'required|exists:pestudios,id',
    ]);

    if ($request->hasFile('archivo')) {
        $archivo = $request->file('archivo');
        $archivoNombre = $archivo->getClientOriginalName();
        $archivoRuta = $archivo->storeAs('archivos', $archivoNombre, 'public');

        $archivoExiste = Taplicacion::where('archivo', $archivoRuta)->where('id', '!=', $taplicacion->id)->exists();
        if ($archivoExiste) {
            return redirect()->route('trabajoAplicacion.edit', $id)
                ->withErrors(['archivo' => 'El archivo con ese nombre ya existe. Por favor, elige otro archivo diferente.'])
                ->withInput();
        }

        if ($taplicacion->archivo) {
            Storage::delete('public/' . $taplicacion->archivo);
        }

        $taplicacion->archivo = $archivoRuta;
    }

    $taplicacion->titulo = $request->titulo;
    $taplicacion->resumen = $request->resumen;
    $taplicacion->save();
    $autoresActualizados = [];

    foreach ($request->autors as $key => $autorNombre) {
        $autorExistente = Autor::where('nombre', $autorNombre)->first();

        if (!$autorExistente) {
            $autorExistente = Autor::create([
                'nombre' => $autorNombre,
                'pestudio_id' => $request->pestudio_id[$key],
            ]);
        } elseif ($autorExistente->pestudio_id != $request->pestudio_id[$key]) {
            return redirect()->route('trabajoAplicacion.edit', $id)
                ->withErrors(['autors.' . $key => "El autor '$autorNombre' ya pertenece a otro programa de estudios."])
                ->withInput();
        }

        $autoresActualizados[] = $autorExistente->id;
    }
    $taplicacion->autores()->sync($autoresActualizados);
    $autoresViejos = Autor::whereDoesntHave('trabajosDeAplicacion')->get();

    foreach ($autoresViejos as $autorViejo) {
        $autorViejo->delete();
    }

    $programasDeEstudio = $taplicacion->autores->pluck('pestudio_id')->unique();
    $taplicacion->tipo = $programasDeEstudio->count() > 1 ? 'Interdisciplinario' : 'Normal';
    $taplicacion->save();

    return redirect()->route('trabajoAplicacion.index')->with('success', 'La aplicación de trabajo se actualizó correctamente.');
}

    public function destroy($id)
    {
        if (auth()->user()) {
            $taplicacion = Taplicacion::findOrFail($id);
            $taplicacion->autores()->detach();
            if ($taplicacion->archivo) {
                Storage::delete('public/' . $taplicacion->archivo);
            }
            $taplicacion->delete();
            $autoresViejos = Autor::whereDoesntHave('trabajosDeAplicacion')->get();
            foreach ($autoresViejos as $autorViejo) {
                $autorViejo->delete();
            }

            return redirect()->route('trabajoAplicacion.index')
                ->with('success', 'El trabajo de aplicación ha sido eliminado exitosamente.');
        } else {
            return redirect()->to('/');
        }
    }

}
