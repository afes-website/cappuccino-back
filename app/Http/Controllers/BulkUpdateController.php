<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLogEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class BulkUpdateController extends Controller {
    private function processEntry($data, User $user): array {
        // Validation
        if (!is_array($data)) return ['is_applied' => false, 'code' => 'BAD_REQUEST'];
        $validator = Validator::make($data, [
            'command' => [
                'required',
                Rule::in('enter', 'exit', 'check-in', 'check-out', 'register-spare'),
            ],
            'guest_id' => ['string', 'required'],
            'reservation_id' => ['string'],
            'timestamp' => ['string', 'required'],
        ]);
        if ($validator->fails()) return ['is_applied' => false, 'code' => 'BAD_REQUEST'];

        if (($data['command'] === 'check-in' || $data['command']  ===  'register-spare')
            && (!array_key_exists('reservation_id', $data))
        ) {
            return ['is_applied' => false, 'code' => 'BAD_REQUEST'];
        }

        // Permission
        $permission_check = null;
        switch ($data['command']) {
            case 'check-in':
            case 'check-out':
            case 'register-spare':
                $permission_check = $user->hasPermission('executive');
                break;
            case 'enter':
            case 'exit':
                $permission_check = $user->hasPermission('exhibition');
                break;
        }
        if (!$permission_check) return ['is_applied' => false, 'code' => 'FORBIDDEN'];

        // Timestamp check
        try {
            $timestamp = Carbon::createFromTimeString($data['timestamp']);
        } catch (\Exception $e) {
            return ['is_applied' => false, 'code' => 'INVALID_TIMESTAMP'];
        }
        if ($timestamp->isFuture()) return ['is_applied' => false, 'code' => 'INVALID_TIMESTAMP'];

        switch ($data['command']) {
            case 'check-in':
                return self::checkIn($data['guest_id'], $data['reservation_id'], $data['timestamp']);
            case 'check-out':
                return self::checkOut($data['guest_id'], $data['timestamp']);
            case 'enter':
                return self::enter($data['guest_id'], $user->id, $data['timestamp']);
            case 'exit':
                return self::exit($data['guest_id'], $user->id, $data['timestamp']);
            case 'register-spare':
                return self::registerSpare($data['guest_id'], $data['reservation_id'], $data['timestamp']);
            default:
                return ['is_applied' => false, 'code' => 'BAD_REQUEST'];
        }
    }

    public function post(Request $request) {
        if (!$request->isJson()) abort(400);
        $content = $request->input();
        $user = $request->user();
        $response = [];
        $ng_count = 0;
        foreach ($content as $item) {
            try {
                $res = $this->processEntry($item, $user);
                if ($res['is_applied'] === false) {
                    $ng_count++;
                    Log::info(
                        'Bulk-update failed',
                        [
                            'code' => $res['code'],
                            'command' => array_key_exists('command', $item) ? $item['command'] : 'undefined',
                            'guest_id' => array_key_exists('guest_id', $item) ? $item['guest_id'] : 'undefined',
                            'reservation_id' =>
                                array_key_exists('reservation_id', $item) ? $item['reservation_id'] : 'undefined',
                            'user_id' => $user->id,
                        ],
                    );
                }
            } catch (\Exception $e) {
                report($e);
                $res = [
                    'is_applied' => false,
                    'code' => 'SERVER_ERROR',
                ];
            } finally {
                $response[] = $res;
            }
        }
        if ($ng_count)
            Log::notice(
                "{$ng_count} bulk-update entry(s) failed",
                ['all count' => count($content), 'user_id' => $user->id],
            );
        return response()->json($response);
    }

    private function checkIn($guest_id, $rsv_id, $timestamp): array {
        ActivityLogEntry::create([
            'log_type' => 'check-in',
            'guest_id' => $guest_id,
            'timestamp' => $timestamp,
            'verified' => false,
        ]);
        $reservation = Reservation::find($rsv_id);

        if (!$reservation)
            return ['is_applied' => false, 'code' => 'RESERVATION_NOT_FOUND'];
        if (Guest::find($guest_id))
            return ['is_applied' => false, 'code' => 'ALREADY_USED_WRISTBAND'];

        Guest::create([
            'id' => $guest_id,
            'term_id' => $reservation->term->id,
            'reservation_id' => $reservation->id,
            'registered_at' => $timestamp,
        ]);
        return ['is_applied' => true, 'code' => null];
    }
    private function checkOut($guest_id, $timestamp): array {
        ActivityLogEntry::create([
            'log_type' => 'check-out',
            'guest_id' => $guest_id,
            'timestamp' => $timestamp,
            'verified' => false,
        ]);

        $guest = Guest::find($guest_id);
        if (!$guest)
            return ['is_applied' => false, 'code' => 'GUEST_NOT_FOUND'];

        $guest->update([
            'revoked_at' => $timestamp
        ]);
        $reservation = $guest->reservation;
        $guests = $reservation->guest;
        if ($guests->whereNotNull('revoked_at')->count() === $reservation->member_all)
            $reservation->revokeAllGuests();

        return ['is_applied' => true, 'code' => null];
    }
    private function enter($guest_id, $exh_id, $timestamp): array {
        ActivityLogEntry::create([
            'log_type' => 'enter',
            'guest_id' => $guest_id,
            'exhibition_id' => $exh_id,
            'timestamp' => $timestamp,
            'verified' => false,
        ]);

        $guest = Guest::find($guest_id);
        if (!$guest)
            return ['is_applied' => false, 'code' => 'GUEST_NOT_FOUND'];

        $guest->updateLocation();
        return ['is_applied' => true, 'code' => null];
    }
    private function exit($guest_id, $exh_id, $timestamp): array {
        ActivityLogEntry::create([
            'log_type' => 'exit',
            'guest_id' => $guest_id,
            'exhibition_id' => $exh_id,
            'timestamp' => $timestamp,
            'verified' => false,
        ]);

        $guest = Guest::find($guest_id);
        if (!$guest)
            return ['is_applied' => false, 'code' => 'GUEST_NOT_FOUND'];

        $guest->updateLocation();
        return ['is_applied' => true, 'code' => null];
    }
    private function registerSpare($guest_id, $rsv_id, $timestamp): array {
        ActivityLogEntry::create([
            'log_type' => 'register-spare',
            'guest_id' => $guest_id,
            'timestamp' => $timestamp,
            'verified' => false,
        ]);
        $reservation = Reservation::find($rsv_id);

        if (!$reservation)
            return ['is_applied' => false, 'code' => 'RESERVATION_NOT_FOUND'];
        if (Guest::find($guest_id))
            return ['is_applied' => false, 'code' => 'ALREADY_USED_WRISTBAND'];

        Guest::create([
            'id' => $guest_id,
            'term_id' => $reservation->term->id,
            'reservation_id' => $reservation->id,
            'registered_at' => $timestamp,
            'is_spare' => true,
        ]);
        return ['is_applied' => true, 'code' => null];
    }
}
