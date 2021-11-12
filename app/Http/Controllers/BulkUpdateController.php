<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    private function processEntry($data, User $user): array {
        // Validation
        if (!is_array($data)) return ['is_ok' => false, 'code' => 'BAD_REQUEST'];
        $validator = Validator::make($data, [
            'command' => [
                'required',
                Rule::in('enter', 'exit', 'check-in', 'check-out', 'register-spare'),
            ],
            'guest_id' => ['string', 'required'],
            'reservation_id' => ['string'],
            'timestamp' => ['string', 'required'],
        ]);
        if ($validator->fails()) return ['is_ok' => false, 'code' => 'BAD_REQUEST'];

        if (($data['command'] === 'check-in' || $data['command']  ===  'register-spare')
            && (!array_key_exists('reservation_id', $data))
        ) {
            return ['is_ok' => false, 'code' => 'BAD_REQUEST'];
        }

        // Permission
        $permission_check = false;
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
        if (!$permission_check) return ['is_ok' => false, 'code' => 'FORBIDDEN'];

        // Timestamp check
        try {
            $data['timestamp'] = new Carbon($data['timestamp']);
        } catch (\Exception $e) {
            return ['is_ok' => false, 'code' => 'INVALID_TIMESTAMP'];
        }
        if ($data['timestamp']->isFuture()) return ['is_ok' => false, 'code' => 'INVALID_TIMESTAMP'];
        $data['timestamp'] = $data['timestamp']->unix();

        switch ($data['command']) {
            case 'check-in':
                return self::checkIn($data);
            case 'check-out':
                return self::checkOut($data);
            case 'enter':
                return self::enter($data, $user->id);
            case 'exit':
                return self::exit($data, $user->id);
            case 'register-spare':
                return self::registerSpare($data);
            default:
                return ['is_ok' => false, 'code' => 'BAD_REQUEST'];
        }
    }

    public function post(Request $request) {
        if (!$request->isJson()) abort(400);
        $content = $request->input();
        $user = $request->user();
        $response = [];
        $ng_count = 0;
        foreach ($content as $item) {
            DB::beginTransaction();
            try {
                $res = $this->processEntry($item, $user);
                if ($res['is_ok'] === false) {
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
                DB::commit();
            } catch (\Exception $e) {
                report($e);
                $res = [
                    'is_ok' => false,
                    'code' => 'INTERNAL_SERVER_ERROR',
                ];
                DB::rollBack();
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

    private function checkIn($data): array {
        ActivityLogEntry::create([
            'log_type' => 'check-in',
            'guest_id' => $data['guest_id'],
            'timestamp' => $data['timestamp'],
            'verified' => false,
        ]);
        $reservation = Reservation::find($data['reservation_id']);

        if (!$reservation)
            return ['is_ok' => false, 'code' => 'RESERVATION_NOT_FOUND'];
        if (Guest::find($data['guest_id']))
            return ['is_ok' => false, 'code' => 'ALREADY_USED_WRISTBAND'];

        Guest::create([
            'id' => $data['guest_id'],
            'term_id' => $reservation->term->id,
            'reservation_id' => $reservation->id,
            'registered_at' => $data['timestamp'],
        ]);
        return ['is_ok' => true, 'code' => null];
    }
    private function checkOut($data): array {
        $log = ActivityLogEntry::create([
            'log_type' => 'check-out',
            'guest_id' => $data['guest_id'],
            'timestamp' => $data['timestamp'],
            'verified' => false,
        ]);

        $guest = Guest::find($data['guest_id']);
        if (!$guest)
            return ['is_ok' => false, 'code' => 'GUEST_NOT_FOUND'];

        $guest->update([
            'revoked_at' => $data['timestamp'],
        ]);
        $guest->updateLocation($log);
        $reservation = $guest->reservation;
        $guests = $reservation->guest;
        if ($guests->whereNotNull('revoked_at')->count() === $reservation->member_all)
            $reservation->revokeAllGuests();

        return ['is_ok' => true, 'code' => null];
    }
    private function enter($data, $exh_id): array {
        $log = ActivityLogEntry::create([
            'log_type' => 'enter',
            'guest_id' => $data['guest_id'],
            'exhibition_id' => $exh_id,
            'timestamp' => $data['timestamp'],
            'verified' => false,
        ]);

        $guest = Guest::find($data['guest_id']);
        if (!$guest)
            return ['is_ok' => true, 'code' => 'GUEST_NOT_FOUND'];

        $guest->updateLocation($log);
        return ['is_ok' => true, 'code' => null];
    }
    private function exit($data, $exh_id): array {
        $log = ActivityLogEntry::create([
            'log_type' => 'exit',
            'guest_id' => $data['guest_id'],
            'exhibition_id' => $exh_id,
            'timestamp' => $data['timestamp'],
            'verified' => false,
        ]);

        $guest = Guest::find($data['guest_id']);
        if (!$guest)
            return ['is_ok' => true, 'code' => 'GUEST_NOT_FOUND'];

        $guest->updateLocation($log);
        return ['is_ok' => true, 'code' => null];
    }
    private function registerSpare($data): array {
        ActivityLogEntry::create([
            'log_type' => 'register-spare',
            'guest_id' => $data['guest_id'],
            'timestamp' => $data['timestamp'],
            'verified' => false,
        ]);
        $reservation = Reservation::find($data['reservation_id']);

        if (!$reservation)
            return ['is_ok' => false, 'code' => 'RESERVATION_NOT_FOUND'];
        if (Guest::find($data['guest_id']))
            return ['is_ok' => false, 'code' => 'ALREADY_USED_WRISTBAND'];

        Guest::create([
            'id' => $data['guest_id'],
            'term_id' => $reservation->term->id,
            'reservation_id' => $reservation->id,
            'registered_at' => $data['timestamp'],
            'is_spare' => true,
        ]);
        return ['is_ok' => true, 'code' => null];
    }
}
