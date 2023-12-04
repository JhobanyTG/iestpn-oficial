<?php

namespace App\Http\Controllers;

use App\Models\Pestudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ProgramaEstudioController extends Controller
{
    public function index()
    {
        if(auth()->user()){
            $programaEstudios = Pestudio::all();
            return view ('programaEstudios.index')->with('programaEstudios',$programaEstudios);
        } else{
            return redirect()->to('/');
        }
    }

    public function create()
    {
        if(auth()->user()){
            $pestudio = Pestudio::all();
            return view ('programaEstudios.create')->with('pestudio',$pestudio)
                ->with('success', 'El programa de estudios ha sido registrado exitosamente.');

        } else{
            return redirect()->to('/');
        }
    }

    public function store(Request $request)
    {
        if(auth()->user()){
            $pestudios = new Pestudio();
            $pestudios->nombre = $request->get('nombre');
            $pestudios->save();
            return redirect('/programaEstudios')
            ->with('success', 'El programa de estudios ha sido guardado exitosamente.');
        } else{
            return redirect()->to('/');
        }


    }
    public function show(Pestudio $pestudio)
    {
        //
    }

    public function edit($id)
    {
        if(auth()->user()){
            $pestudio = Pestudio::find($id);
            return view('programaEstudios.edit')->with('pestudio',$pestudio)
            ->with('success', 'El programa de estudios ha sido actualizado exitosamente.');

        } else{
            return redirect()->to('/');
        }

    }
    
    public function update(Request $request, $id)
    {

        $pestudio = Pestudio::find($id);
        $pestudio->nombre = $request->get('nombre');

        $pestudio->save();

        return redirect('/programaEstudios')
            ->with('success', 'El programa de estudios ha sido actualizado exitosamente.');
    }
}
