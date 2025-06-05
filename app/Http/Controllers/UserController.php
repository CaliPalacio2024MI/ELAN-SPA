<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function create()
{
    $path = resource_path('views/users/create.blade.php');

    if (!file_exists($path)) {
        return response()->json(['error' => 'La vista NO existe en ' . $path], 404);
    }

    return response()->json(['success' => 'La vista sí existe en ' . $path], 200);
}

    public function store(Request $request)
    {
        $user = Auth::user();

        
        $request->validate([
            'name' => 'required|string|max:255',
            'rfc' => 'required|string|max:13|unique:users',
            'rol' => 'required|string|in:master,administrador,recepcionista',
            'area' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        if ($user->rol === 'administrador' && $request->rol !== 'recepcionista') {
            return redirect()->back()->with('error', 'No tienes permiso para crear este tipo de usuario.');
        }


        User::create([
            'name' => $request->name,
            'rfc' => $request->rfc,
            'rol' => $request->rol,
            'area' => $request->area,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.create')->with('success', 'Usuario creado correctamente.');
    }
}

