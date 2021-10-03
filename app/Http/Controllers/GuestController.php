<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpExceptionWithErrorCode;
use App\Models\Exhibition;
use App\Resources\GuestResource;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLogEntry;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller {
    private function findGuestOrFail($guest_id, $http_code) {
        $guest = Guest::find($guest_id);
        if (!$guest) throw new HttpExceptionWithErrorCode($http_code, 'GUEST_NOT_FOUND');
        return $guest;
    }
    private function findReservationOrFail($reservation_id) {
        $reservation = Reservation::find($reservation_id);
        if (!$reservation) throw new HttpExceptionWithErrorCode(400, 'RESERVATION_NOT_FOUND');
        return $reservation;
    }
    private function findExhibitionOrFail($exhibition_id) {
        $exhibition = Exhibition::find($exhibition_id);
        if (!$exhibition) throw new HttpExceptionWithErrorCode(400, 'EXHIBITION_NOT_FOUND');
        return $exhibition;
    }
    private function validateWristbandCode($guest_id) {
        if (!Guest::validate($guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'INVALID_WRISTBAND_CODE');
        }
    }
    private function validateWristbandColor($guest_id, $guest_type) {
        if (strpos($guest_id, config('cappuccino.guest_types')[$guest_type]['prefix']) !== 0
        ) {
            throw new HttpExceptionWithErrorCode(400, 'WRONG_WRISTBAND_COLOR');
        }
    }
    private function checkWristbandUnused($guest_id) {
        if (Guest::find($guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'ALREADY_USED_WRISTBAND');
        }
    }
    private function checkGuestNotExited($guest) {
        if ($guest->revoked_at !== null) {
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');
        }
    }
    private function checkGuestNotEntered($guest, $exhibition) {
        if ($guest->exhibition_id === $exhibition->id)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_ENTERED');
    }
    private function checkExhibitionNotFull($exhibition) {
        if ($exhibition->capacity <= $exhibition->guests()->count())
            throw new HttpExceptionWithErrorCode(400, 'PEOPLE_LIMIT_EXCEEDED');
    }
    private function checkGuestExitTimeNotExceeded($guest) {
                if ($guest->term->exit_scheduled_time < Carbon::now())
                    throw new HttpExceptionWithErrorCode(400, 'EXIT_TIME_EXCEEDED');
    }

    public function show($id) {
        $id = strtoupper($id);
        $guest = self::findGuestOrFail($id, 404);
        return response()->json(new GuestResource($guest));
    }

    public function index() {
        return response()->json(GuestResource::collection(Guest::all()));
    }

    public function checkIn(Request $request) {
        $this->validate($request, [
            'reservation_id' => ['string', 'required'],
            'guest_id' => ['string', 'required']
        ]);
        $guest_id = strtoupper($request->guest_id);
        $reservation = self::findReservationOrFail($request->reservation_id);
        $term = $reservation->term;

        if (($reservation_error_code = $reservation->getErrorCode()) !== null) {
            throw new HttpExceptionWithErrorCode(400, $reservation_error_code);
        }

        self::validateWristbandCode($guest_id);
        self::checkWristbandUnused($guest_id);
        self::validateWristbandColor($guest_id, $term->guest_type);

        return DB::transaction(function () use ($request, $term, $guest_id, $reservation) {
            $guest = Guest::create(
                [
                    'id' => $guest_id,
                    'term_id' => $term->id,
                    'reservation_id' => $reservation->id
                ]
            );
            $reservation->update(['guest_id' => $guest->id]);
            ActivityLogEntry::create([
                'log_type' => 'check-in',
                'guest_id' => $guest->id
            ]);
            return response()->json(new GuestResource($guest));
        });

        // TODO: 複数人で処理するときの扱いを考える (docsの編集待ち)
    }

    public function registerSpare(Request $request) {
        $this->validate($request, [
            'reservation_id' => ['string', 'required'],
            'guest_id' => ['string', 'required']
        ]);
        $guest_id = strtoupper($request->guest_id);
        $reservation = self::findReservationOrFail($request->reservation_id);
        $term = $reservation->term;

        if ($reservation->guest()->count() === 0) {
            throw new HttpExceptionWithErrorCode(400, 'NO_MEMBER_CHECKED_IN');
        }
        if ($reservation->term->exit_scheduled_time < Carbon::now()) {
            throw new HttpExceptionWithErrorCode(400, 'EXIT_TIME_EXCEEDED');
        }
        self::validateWristbandCode($guest_id);
        self::checkWristbandUnused($guest_id);

        return DB::transaction(function () use ($request, $term, $guest_id, $reservation) {
            $guest = Guest::create(
                [
                    'id' => $guest_id,
                    'is_spare' => true,
                    'term_id' => $term->id,
                    'reservation_id' => $reservation->id,
                ]
            );
            $reservation->update(['guest_id' => $guest->id]);
            ActivityLogEntry::create([
                'log_type' => 'register-spare',
                'guest_id' => $guest->id
            ]);
            return response()->json(new GuestResource($guest));
        });
    }

    public function checkOut($id) {
        $id = strtoupper($id);
        $guest = self::findGuestOrFail($id, 404);
        self::checkGuestNotExited($guest);

        $guest->update(['revoked_at' => Carbon::now()]);
        ActivityLogEntry::create([
            'log_type' => 'check-out',
            'guest_id' => $guest->id
        ]);

        return response()->json(new GuestResource($guest));
    }

    public function enter(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);
        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        $id = strtoupper($id);
        $guest = self::findGuestOrFail($id, 404);
        $exhibition = self::findExhibitionOrFail($request->exhibition_id);

        self::checkGuestNotEntered($guest, $exhibition);
        self::checkExhibitionNotFull($exhibition);
        self::checkGuestNotExited($guest);
        self::checkGuestExitTimeNotExceeded($guest);

        return DB::transaction(function () use ($guest, $exhibition) {
            $guest->update(['exhibition_id' => $exhibition->id]);
            ActivityLogEntry::create([
                'exhibition_id' => $exhibition->id,
                'log_type' => 'enter',
                'guest_id' => $guest->id
            ]);

            return response()->json(new GuestResource($guest));
        });
    }

    public function exit(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);
        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        $id = strtoupper($id);
        $guest = $this->findGuestOrFail($id, 404);
        $exhibition = self::findExhibitionOrFail($request->exhibition_id);

        self::checkGuestNotExited($guest);

        return DB::transaction(function () use ($guest, $exhibition) {
            $guest->update(['exhibition_id' => null]);
            ActivityLogEntry::create([
                'exhibition_id' => $exhibition->id,
                'log_type' => 'exit',
                'guest_id' => $guest->id
            ]);
            return response()->json(new GuestResource($guest));
        });
    }
}
