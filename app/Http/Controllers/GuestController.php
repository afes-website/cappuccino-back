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
use Illuminate\Support\Facades\Log;

class GuestController extends Controller {
    public function show($id) {
        return response()->json(new GuestResource(Guest::FindOrFail($id)));
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
        $reservation = Reservation::findOrFail($request->reservation_id, 400);
        $term = $reservation->term;

        if (($reservation_error_code = $reservation->getErrorCode()) !== null)
            throw new HttpExceptionWithErrorCode(400, $reservation_error_code);

        Guest::assertCanBeRegistered($guest_id, $term->guest_type);

        return DB::transaction(function () use ($request, $term, $guest_id, $reservation) {
            $guest = Guest::create(
                [
                    'id' => $guest_id,
                    'term_id' => $term->id,
                    'reservation_id' => $reservation->id,
                    'is_spare' => false
                ]
            );
            ActivityLogEntry::create([
                'log_type' => 'check-in',
                'guest_id' => $guest->id,
                'exhibition_id' => null
            ]);
            return response()->json(new GuestResource($guest));
        });
    }

    public function registerSpare(Request $request) {
        $this->validate($request, [
            'reservation_id' => ['string', 'required'],
            'guest_id' => ['string', 'required']
        ]);
        $guest_id = strtoupper($request->guest_id);
        $reservation = Reservation::findOrFail($request->reservation_id, 400);
        $term = $reservation->term;

        if ($reservation->guest()->count() === 0)
            throw new HttpExceptionWithErrorCode(400, 'NO_MEMBER_CHECKED_IN');
        if ($reservation->term->exit_scheduled_time < Carbon::now())
            throw new HttpExceptionWithErrorCode(400, 'EXIT_TIME_EXCEEDED');

        Guest::assertCanBeRegistered($guest_id, $term->guest_type);

        return DB::transaction(function () use ($request, $term, $guest_id, $reservation) {
            $guest = Guest::create(
                [
                    'id' => $guest_id,
                    'is_spare' => true,
                    'term_id' => $term->id,
                    'reservation_id' => $reservation->id,
                ]
            );
            ActivityLogEntry::create([
                'log_type' => 'register-spare',
                'guest_id' => $guest->id,
                'exhibition_id' => null
            ]);
            return response()->json(new GuestResource($guest));
        });
    }

    public function checkOut($id) {
        return DB::transaction(function () use ($id) {
            $guest = Guest::FindOrFail($id);
            if ($guest->revoked_at !== null)
                throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');

            $guest->update(['revoked_at' => Carbon::now()]);
            ActivityLogEntry::create([
                'log_type' => 'check-out',
                'guest_id' => $guest->id,
                'exhibition_id' => null
            ]);

            $reservation = $guest->reservation;
            $guests = $reservation->guest();
            if ($guests->whereNotNull('revoked_at')->count() === $reservation->member_all)
                $reservation->revokeAllGuests();
            return response()->json(new GuestResource($guest));
        });
    }

    public function enter(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);
        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        $guest = Guest::FindOrFail($id);
        $exhibition = Exhibition::findOrFail($request->exhibition_id, 400);

        if ($guest->exhibition_id === $exhibition->id)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_ENTERED');
        if ($exhibition->capacity <= $exhibition->guests()->count())
            throw new HttpExceptionWithErrorCode(400, 'PEOPLE_LIMIT_EXCEEDED');
        if ($guest->revoked_at !== null)
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
        if (!$request->user()->hasPermission('admin') && $request->exhibition_id !== $request->user()->id)
            abort(403);

        $guest = Guest::FindOrFail($id);
        $exhibition = Exhibition::findOrFail($request->exhibition_id, 400);

        if ($guest->revoked_at !== null)
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
