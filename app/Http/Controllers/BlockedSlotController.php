<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlockedSlot;
use App\Models\Spa;
use Illuminate\Support\Facades\Log;

class BlockedSlotController extends Controller
{
    public function store(Request $request)
    {
        Log::info(' Datos recibidos para bloqueo:', $request->all());
        try {
            $validated = $request->validate([
                'anfitrion_id' => 'required|exists:anfitriones,id',
                'fecha' => 'required|date',
                'hora' => 'required|date_format:H:i',
                'duracion' => 'required|integer|min:5|max:180',
                'motivo' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }

        $spa = Spa::where('nombre', session('current_spa'))->firstOrFail();
        $validated['spa_id'] = $spa->id;

        BlockedSlot::create($validated);

        if ($request->ajax()) {
            return view('reservations.table', compact(
                'anfitriones',
                'reservaciones',
                'bloqueos',
                'cabinas',
                'cabinasOcupadas'
            ));
        }        

        return response()->json([
            'success' => true,
            'message' => 'Celda bloqueada correctamente.'
        ]);
    }

    
}
