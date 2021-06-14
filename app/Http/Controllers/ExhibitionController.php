<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpExceptionWithErrorCode;
use App\Resources\ActivityLogEntryResource;
use App\Resources\ExhibitionResource;
use App\Resources\GuestResource;
use App\Models\Exhibition;
use App\Models\Guest;
use App\Models\Term;
use App\Models\ActivityLogEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExhibitionController extends Controller {
    public function index() {
        $exh_status = [];
        $all_limit = 0;
        foreach (Exhibition::with('guests')->get() as $exh) {
            $all_limit += $exh->capacity;
            $exh_status[$exh->id] = new ExhibitionResource($exh);
        }

        return response()->json([
            'exhibition' => $exh_status,
            'all' => Guest::query()
                ->whereNull('exited_at')
                ->select('term_id', DB::raw('count(1) as cnt'))
                ->groupBy('term_id')
                ->pluck('cnt', 'term_id')
        ]);
    }

    public function show(Request $request, $id) {
        $exhibition = Exhibition::find($id);
        if (!$exhibition) {
            abort(404);
        }

        return response()->json(new ExhibitionResource($exhibition));
    }

    public function enter(Request $request, $id) {
        $this->validate($request, [
            'exhibition_id' => ['string', 'required']
        ]);

        $exhibition_id = $request->exhibition_id;

        $user_id = $request->user()->id;
        $guest = Guest::find($id);

        if (!$request->user()->hasPermission('admin') && $exhibition_id !== $user_id)
            abort(403);

        $exhibition = Exhibition::find($exhibition_id);

        if (!$exhibition) throw new HttpExceptionWithErrorCode(400, 'EXHIBITION_NOT_FOUND');
        if (!$guest) throw new HttpExceptionWithErrorCode(404, 'GUEST_NOT_FOUND');

        if ($guest->exhibition_id === $exhibition->id)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_ENTERED');

        if ($exhibition->capacity === $exhibition->guests->count())
            throw new HttpExceptionWithErrorCode(400, 'PEOPLE_LIMIT_EXCEEDED');

        if ($guest->exited_at !== null)
            throw new HttpExceptionWithErrorCode(400, 'GUEST_ALREADY_EXITED');

        if ($guest->term->exit_scheduled_time < Carbon::now())
            throw new HttpExceptionWithErrorCode(400, 'EXIT_TIME_EXCEEDED');


        $guest->update(['exhibition_id' => $exhibition->id]);

        ActivityLogEntry::create([
            'exhibition_id' => $exhibition->id,
            'log_type' => 'enter',
            'guest_id' => $guest->id
        ]);

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

        $guest->update(['exhibition_id' => null]);

        ActivityLogEntry::create([
            'exhibition_id' => $exhibition->id,
            'log_type' => 'exit',
            'guest_id' => $guest->id
        ]);

        return response()->json(new GuestResource($guest));
    }
}
