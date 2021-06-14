<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpExceptionWithErrorCode;
use App\Models\Exhibition;
use App\Resources\ActivityLogEntryResource;
use App\Resources\GuestResource;
use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLogEntry;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller {
    public function show(Request $request, $id) {
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

        if (!preg_match('/\A[A-Z]{2,3}-[2-578ac-kmnpr-z]{5}\Z/', $request->guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'INVALID_WRISTBAND_CODE');
        }

        $reservation = Reservation::find($request->reservation_id);

        if (!$reservation) throw new HttpExceptionWithErrorCode(400, 'RESERVATION_NOT_FOUND');

        if (($reservation_error_code = $reservation->getErrorCode()) !== null) {
            throw new HttpExceptionWithErrorCode(400, $reservation_error_code);
        }

        if (Guest::find($request->guest_id)) {
            throw new HttpExceptionWithErrorCode(400, 'ALREADY_USED_WRISTBAND');
        }

        $term = $reservation->term;

        if (strpos($request->guest_id, config('cappuccino.guest_types')[$term->guest_type]['prefix']) !== 0
        ) {
            throw new HttpExceptionWithErrorCode(400, 'WRONG_WRISTBAND_COLOR');
        }


        DB::transaction(function () use ($request, $reservation, $term) {
            $guest = Guest::create(
                [
                    'id' => $request->guest_id,
                    'term_id' => $term->id,
                    'reservation_id' => $request->reservation_id
                ]
            );

            $reservation->update(['guest_id' => $guest->id]);
        });


        // TODO: 複数人で処理するときの扱いを考える (docsの編集待ち)

        return response()->json(new GuestResource(Guest::find($request->guest_id)));
    }

    public function checkOut($id) {

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

        DB::transaction(function () use ($guest, $exhibition) {
            $guest->update(['exhibition_id' => $exhibition->id]);

            ActivityLogEntry::create([
                'exhibition_id' => $exhibition->id,
                'log_type' => 'enter',
                'guest_id' => $guest->id
            ]);
        });

        return response()->json(new GuestResource($guest));
    }

    public function exit(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);

        $exhibition_id = $request->exhibition_id;
        $user_id = $request->user()->id;
        $guest = Guest::find($id);
        $exhibition = Exhibition::find($exhibition_id);

        if (!$request->user()->hasPermission('reservation') && $exhibition_id !== $user_id)
            abort(403);

        if (!$exhibition) throw new HttpExceptionWithErrorCode(400, 'EXHIBITION_NOT_FOUND');
        if (!$guest) throw new HttpExceptionWithErrorCode(404, 'GUEST_NOT_FOUND');

        if ($guest->exited_at !== null)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');

        DB::transaction(function () use ($guest, $exhibition) {
            $guest->update(['exhibition_id' => null]);

            ActivityLogEntry::create([
                'exhibition_id' => $exhibition->id,
                'log_type' => 'exit',
                'guest_id' => $guest->id
            ]);
        });

        return response()->json(new GuestResource($guest));
    }
}
