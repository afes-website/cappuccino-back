<?php

namespace App\Http\Controllers;

use App\Resources\TermResource;
use App\Models\Term;

class TermController extends Controller {
    public function index() {
        $terms = Term::all();
        $result = [];
        foreach ($terms as $term) {
            $result[$term->id] = new TermResource($term);
        }

        return response()->json($result);
    }
}
