<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Resources\GuestResource;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLogEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkUpdateController extends Controller {
    public function post(Request $request) {
        $content = $request->getContent();
        $json = json_decode($content);
        if (!$json) abort(400);
    }
}
