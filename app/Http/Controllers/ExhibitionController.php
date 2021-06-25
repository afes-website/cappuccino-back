<?php

namespace App\Http\Controllers;

use App\Resources\ExhibitionResource;
use App\Models\Exhibition;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;

class ExhibitionController extends Controller {
    public function index() {
        $exh_status = [];
        $all_limit = 0;
        foreach (Exhibition::with('guests')->get() as $exh) {
            $all_limit += $exh->capacity;
            $exh_status[$exh->id] = new ExhibitionResource($exh);
        }

        return response()->json([
            'exhibition' => $exh_status,
            'all' => [
                'count' => Guest::query()
                    ->whereNull('exited_at')
                    ->select('term_id', DB::raw('count(1) as cnt'))
                    ->groupBy('term_id')
                    ->pluck('cnt', 'term_id'),
                'capacity' => $all_limit
            ]
        ]);
    }

    public function show($id) {
        $exhibition = Exhibition::find($id);
        if (!$exhibition) {
            abort(404);
        }

        return response()->json(new ExhibitionResource($exhibition));
    }
}
