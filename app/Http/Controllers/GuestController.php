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
use Illuminate\Support\Facades\DB;

class GuestController extends Controller {
    public function show($id) {
        $id = strtoupper($id);
        $guest = Guest::find($id);
        if (!$guest) {
            abort(404);
        }

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
        if (!Guest::validate($request->guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'INVALID_WRISTBAND_CODE');
        }

        $guest_id = strtoupper($request->guest_id);
        $reservation_id = strtoupper($request->reservation_id);

        $reservation = Reservation::find($reservation_id);

        if (!$reservation) throw new HttpExceptionWithErrorCode(400, 'RESERVATION_NOT_FOUND');

        if (($reservation_error_code = $reservation->getErrorCode()) !== null) {
            throw new HttpExceptionWithErrorCode(400, $reservation_error_code);
        }

        if (Guest::find($guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'ALREADY_USED_WRISTBAND');
        }

        $term = $reservation->term;

        if (strpos($guest_id, config('cappuccino.guest_types')[$term->guest_type]['prefix']) !== 0
        ) {
            throw new HttpExceptionWithErrorCode(400, 'WRONG_WRISTBAND_COLOR');
        }

        return DB::transaction(function () use ($request, $term, $guest_id, $reservation) {
            $guest = Guest::create(
                [
                    'id' => $guest_id,
                    'term_id' => $term->id,
                    'reservation_id' => $reservation->id
                ]
            );
            $reservation->update(['guest_id' => $guest->id]);
            return response()->json(new GuestResource($guest));
        });

        // TODO: 複数人で処理するときの扱いを考える (docsの編集待ち)
    }

    public function checkOut($id) {
        $id = strtoupper($id);

        $guest = Guest::find($id);
        if (!$guest) {
            throw new HttpExceptionWithErrorCode(404, 'GUEST_NOT_FOUND');
        }

        if ($guest->exited_at !== null) {
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');
        }

        $guest->update(['exited_at' => Carbon::now()]);

        return response()->json(new GuestResource($guest));
    }

    public function enter(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);
        $id = strtoupper($id);

        $guest = Guest::find($id);

        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        $exhibition = Exhibition::find($request->exhibition_id);

        if (!$exhibition) throw new HttpExceptionWithErrorCode(400, 'EXHIBITION_NOT_FOUND');
        if (!$guest) throw new HttpExceptionWithErrorCode(404, 'GUEST_NOT_FOUND');

        if ($guest->exhibition_id === $exhibition->id)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_ENTERED');

        if ($exhibition->capacity <= $exhibition->guests()->count())
            throw new HttpExceptionWithErrorCode(400, 'PEOPLE_LIMIT_EXCEEDED');

        if ($guest->exited_at !== null)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');

        if ($guest->term->exit_scheduled_time < Carbon::now())
            throw new HttpExceptionWithErrorCode(400, 'EXIT_TIME_EXCEEDED');

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
        $id = strtoupper($id);

        $exhibition_id = $request->exhibition_id;
        $guest = Guest::find($id);
        $exhibition = Exhibition::find($exhibition_id);

        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        if (!$exhibition) throw new HttpExceptionWithErrorCode(400, 'EXHIBITION_NOT_FOUND');
        if (!$guest) throw new HttpExceptionWithErrorCode(404, 'GUEST_NOT_FOUND');

        if ($guest->exited_at !== null)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');

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
