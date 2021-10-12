<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpExceptionWithErrorCode;
use App\Resources\ExhibitionResource;
use App\Models\Exhibition;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;

class ExhibitionController extends Controller {
    public function index() {
        $exh_status = [];
        $all_capacity = 0;
        foreach (Exhibition::with('guests')->get() as $exh) {
            $all_capacity += $exh->capacity;
            $exh_status[$exh->id] = new ExhibitionResource($exh);
        }

        return response()->json([
            'exhibition' => $exh_status,
            'all' => [
                'count' => Guest::query()
                    ->whereNull('revoked_at')
                    ->select('term_id', DB::raw('count(1) as cnt'))
                    ->groupBy('term_id')
                    ->pluck('cnt', 'term_id'),
                'capacity' => $all_capacity
            ]
        ]);
    }

    public function show($id) {
        return response()->json(new ExhibitionResource(Exhibition::findOrFail($id)));
    }
}
