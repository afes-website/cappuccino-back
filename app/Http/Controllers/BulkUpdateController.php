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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class BulkUpdateController extends Controller {
    private function checkRequest(Request $request, $data): ?string {
        // Validation
        if (!is_array($data)) return 'BAD_REQUEST';
        $validator = Validator::make($data, [
            'command' => [
                'required',
                Rule::in('enter', 'exit', 'check-in', 'check-out', 'register-spare'),
            ],
            'guest_id' => ['string', 'required'],
            'reservation_id' => ['string'],
            'timestamp' => ['string', 'required'],
        ]);
        if ($validator->fails()) return 'BAD_REQUEST';

        // Permission
        $permission_check = null;
        switch ($data['command']) {
            case 'check-in':
            case 'check-out':
            case 'register-spare':
                $permission_check = $request->user()->hasPermission('executive');
                break;
            case 'enter':
            case 'exit':
                $permission_check = $request->user()->hasPermission('exhibition');
                break;
        }
        if (!$permission_check) return 'FORBIDDEN';

        // Timestamp check
        $timestamp = strtotime($data['timestamp']);
        if (!$timestamp || $timestamp === -1) return ('INVALID_TIMESTAMP');

        return null;
    }

    public function post(Request $request) {
        $content = $request->getContent();
        $json = json_decode($content, true);
        if (!$json) abort(400, $content);
        $response = [];
        foreach ($json as $item) {
            $check = $this->checkRequest($request, $item);
            if ($check) {
                $response[] = ['is_applied' => false, 'code' => $check];
                continue;
            }

            $response[] = true;
        }
        return response()->json($response);
    }
}
