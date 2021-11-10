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
    private function handleRequest(Request $request, $data): ?string {
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

        if (($data['command'] === 'check-in' || $data['command']  ===  'register-spare')
            && array_key_exists('reservation_id', $data)
        ) {
            return 'BAD_REQUEST';
        }

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
        try {
            $timestamp = Carbon::createFromTimeString($data['timestamp']);
        } catch (\Exception $e) {
            return 'INVALID_TIMESTAMP';
        }
        if ($timestamp->isFuture()) return 'INVALID_TIMESTAMP';

        $guest_id = $data['guest_id'];
        $rsv_id = $data['reservation_id'];
        $user_id = $request->user()->id;
        $timestamp = $data['timestamp'];

        switch ($data['command']) {
            case 'check-in':
                $response = self::checkIn($guest_id, $rsv_id, $timestamp);
                break;
            case 'check-out':
                $response = self::checkOut($guest_id, $timestamp);
                break;
            case 'enter':
                $response = self::enter($guest_id, $user_id, $timestamp);
                break;
            case 'exit':
                $response = self::exit($guest_id, $user_id, $timestamp);
                break;
            case 'register-spare':
                $response = self::registerSpare($guest_id, $rsv_id, $timestamp);
                break;
            default:
                $response = 'BAD_REQUEST';
        }

        return $response;
    }

    public function post(Request $request) {
        $content = $request->getContent();
        $json = json_decode($content, true);
        if (!$json) abort(400, $content);
        $response = [];
        foreach ($json as $item) {
            $check = $this->handleRequest($request, $item);
            if ($check) {
                $response[] = ['is_applied' => false, 'code' => $check];
            } else {
                $response[] = ['is_applied' => true, 'code' => null];
            }
        }
        return response()->json($response);
    }

    private function checkIn($guest_id, $rsv_id, $timestamp): ?string {
        return null;
    }
    private function checkOut($guest_id, $timestamp): ?string {
        return null;
    }
    private function enter($guest_id, $exh_id, $timestamp): ?string {
        return null;
    }
    private function exit($guest_id, $exh_id, $timestamp): ?string {
        return null;
    }
    private function registerSpare($guest_id, $rsv_id, $timestamp): ?string {
        return null;
    }
}
