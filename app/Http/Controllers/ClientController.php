<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    // Lista clientes con búsqueda opcional
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('apellido_paterno', 'like', "%$search%")
                  ->orWhere('apellido_materno', 'like', "%$search%")
                  ->orWhere('correo', 'like', "%$search%")
                  ->orWhere('telefono', 'like', "%$search%")
                  ->orWhere('tipo_visita', 'like', "%$search%");
            });
        }

        $clientes = $query->orderBy('nombre')->get();

        return view('gestores.gestor_cliente', compact('clientes'));
    }

    // Valida y crea nuevo cliente
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:150|unique:clients,correo',
            'telefono' => 'required|string|max:20',
            'tipo_visita' => 'required|in:palacio mundo imperial,princess mundo imperial,pierre mundo imperial,condominio,locales',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('cliente.index')
                ->withErrors($validator, 'create')
                ->withInput();
        }

        Client::create($request->all());

        return redirect()->route('cliente.index')->with('success', 'Cliente creado correctamente.');
    }

    // Valida y actualiza cliente existente
    public function update(Request $request, $id)
    {
        $cliente = Client::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:150|unique:clients,correo,' . $cliente->id,
            'telefono' => 'required|string|max:20',
            'tipo_visita' => 'required|in:palacio mundo imperial,princess mundo imperial,pierre mundo imperial,condominio,locales',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('cliente.index')
                ->withErrors($validator, 'edit')
                ->withInput();
        }

        $cliente->update($request->all());

        return redirect()->route('cliente.index')->with('success', 'Cliente actualizado correctamente.');
    }

    // Exporta clientes en formato Excel simple (HTML table)
    public function export(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                  ->orWhere('apellido_paterno', 'like', "%$search%")
                  ->orWhere('apellido_materno', 'like', "%$search%")
                  ->orWhere('correo', 'like', "%$search%")
                  ->orWhere('telefono', 'like', "%$search%")
                  ->orWhere('tipo_visita', 'like', "%$search%");
            });
        }

        $clientes = $query->orderBy('nombre')->get();

        $filename = "clientes_" . now()->format('Ymd_His') . ".xls";

        $headers = [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $content = '
        <table border="1">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido Paterno</th>
                    <th>Apellido Materno</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Tipo de Visita</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($clientes as $cliente) {
            $content .= '
                <tr>
                    <td>' . htmlspecialchars($cliente->nombre) . '</td>
                    <td>' . htmlspecialchars($cliente->apellido_paterno) . '</td>
                    <td>' . htmlspecialchars($cliente->apellido_materno ?? '-') . '</td>
                    <td>' . htmlspecialchars($cliente->correo ?? '-') . '</td>
                    <td>' . htmlspecialchars($cliente->telefono) . '</td>
                    <td>' . htmlspecialchars($cliente->tipo_visita) . '</td>
                </tr>';
        }

        $content .= '
            </tbody>
        </table>';

        return response($content, 200, $headers);
    }

    // Elimina un cliente
    public function destroy($id)
    {
        $cliente = Client::findOrFail($id);
        $cliente->delete();

        return redirect()->route('cliente.index')->with('success', 'Cliente eliminado correctamente.');
    }

    // Importa clientes desde CSV validando columnas y datos
    public function importCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');

            $expectedHeaders = ['nombre', 'apellido_paterno', 'apellido_materno', 'correo', 'telefono', 'tipo_visita'];
            $header = array_map('trim', $header);

            if ($header !== $expectedHeaders) {
                fclose($handle);
                return redirect()->back()->with('error', 'El archivo CSV no tiene las columnas esperadas.');
            }

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $clienteData = array_combine($header, $data);
                if (isset($clienteData['tipo_visita'])) {
                    $clienteData['tipo_visita'] = strtolower(trim($clienteData['tipo_visita']));
                }

                $validator = Validator::make($clienteData, [
                    'nombre' => 'required|string|max:255',
                    'apellido_paterno' => 'required|string|max:255',
                    'apellido_materno' => 'nullable|string|max:255',
                    'correo' => 'nullable|email|max:150|unique:clients,correo',
                    'telefono' => 'required|string|max:20',
                    'tipo_visita' => 'required|in:palacio mundo imperial,princess mundo imperial,pierre mundo imperial,condominio,locales',
                ]);

                if ($validator->fails()) {
                    continue;
                }

                Client::create($clienteData);
            }

            fclose($handle);
        }

        return redirect()->route('cliente.index')->with('success', 'Clientes importados correctamente.');
    }
}
