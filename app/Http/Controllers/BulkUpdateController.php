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
    public function post(Request $request) {
        $content = $request->getContent();
        $json = json_decode($content, true);
        if (!$json) abort(400, $content);
        $response = [];
        foreach ($json as $item) {
            // Validation
            if (!is_array($item)) {
                $response[] = ['is_applied' => false, 'code' => 'BAD_REQUEST'];
                continue;
            }
            $validator = Validator::make($item, [
                'command' => [
                    'required',
                    Rule::in('enter', 'exit', 'check-in', 'check-out', 'register-spare'),
                ],
                'guest_id' => ['string', 'required'],
                'timestamp' => ['string', 'required'],
            ]);
            if ($validator->fails()) {
                $response[] = ['is_applied' => false, 'code' => 'BAD_REQUEST'];
                continue;
            }

            // Permission
            $permission_check = null;
            switch ($item['command']) {
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
            if (!$permission_check) {
                $response[] = ['is_applied' => false, 'code' => 'FORBIDDEN'];
                continue;
            }

            // Timestamp check
            $timestamp = strtotime($item['timestamp']);
            if ($timestamp === -1) {
                $response[] = ['is_applied' => false, 'code' => 'INVALID_TIMESTAMP'];
                continue;
            }

            $response[] = true;
        }
        return response()->json($response);
    }
}
