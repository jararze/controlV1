<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ScoreCardController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::connection('external_db')
            ->table('logistica_transporte');

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('patente', 'LIKE', $searchTerm)
                    ->orWhere('ruta', 'LIKE', $searchTerm)
                    ->orWhere('origen', 'LIKE', $searchTerm)
                    ->orWhere('destino', 'LIKE', $searchTerm);
            });
        }

        // Handle sorting
        $sort = $request->input('sort', 'fecha_creacion'); // Default sort by fecha
        $direction = $request->input('direction', 'desc'); // Default direction is descending (newest first)

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['patente', 'ruta', 'origen', 'destino', 'km_recorridos', 'fecha_creacion'];
        if (in_array($sort, $allowedSortFields)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('fecha_creacion', 'desc');
        }

        // Get paginated results
        $logisticaData = $query->paginate(50)->withQueryString();

        // If this is an AJAX request, return only the table partial view
        if ($request->ajax()) {
            return view('scoreCard.table-partial', compact('logisticaData'))->render();
        }

        return view('scoreCard.index', compact('logisticaData'));
    }

    public function show($id)
    {
        // Obtener un registro específico
        $record = DB::table('logistica_transporte')->where('id', $id)->first();

        if (!$record) {
            return redirect()->route('scoreCard.index')->with('error', 'Registro no encontrado');
        }

        return view('scoreCard.show', compact('record'));
    }
}
