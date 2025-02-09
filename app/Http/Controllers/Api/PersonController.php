<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index() {
        $persons = Person::orderBy('created_at', 'desc')->get();
        return view('welcome', ['persons' => $persons]);
    }

    public function create()
    {
        return view('create');
    }

    // Enregistrer une nouvelle personne
    public function store(Request $request)
    {
        // Validation des données
        $request->validate([
            'npi' => 'required|digits:10',
            'name' => 'required|string|max:255',
            'birthday' => 'required|date',
            'number' => 'required|integer',
        ]);

        // Enregistrement dans la base de données
        Person::create($request->all());

        // Redirection avec un message de succès
        return redirect()->route('create')->with('success', 'Personne enregistrée avec succès !');
    }

}
