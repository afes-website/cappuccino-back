<?php

namespace App\Http\Controllers;

use App\Resources\ActivityLogEntryResource;
use App\Models\ActivityLogEntry;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class ActivityLogController extends BaseController {

    public function index(Request $request) {
        $query = $this->validate($request, [
            'id' => ['int'],
            'timestamp' => ['string'],
            'guest_id' => ['string'],
            'exhibition_id' => ['string'],
            'log_type' => ['string'],
        ]);
        $log = ActivityLogEntry::query();


        foreach ($query as $i => $value) {
            $log->where($i, $value);
        }

        return response()->json(ActivityLogEntryResource::collection($log->get()));
    }
}
