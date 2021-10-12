<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpExceptionWithErrorCode;
use App\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller {

    public function search(Request $request) {
        $query = $this->validate($request, [
            'term_id' => ['string'],
        ]);

        $data = Reservation::query();

        foreach ($query as $i => $value) $data->where($i, $value);

        return response(ReservationResource::collection($data->get()));
    }

    public function show($id) {
        return response()->json(new ReservationResource(Reservation::findOrFail($id)));
    }

    public function check($id) {
        $reservation = Reservation::findOrFail($id);
        $status_code = $reservation->getErrorCode();
        if ($status_code !== null) {
            $valid = false;
        } else {
            $valid = true;
        }

        $res = [
            'valid' => $valid,
            'error_code' => $status_code,
            'reservation' => new ReservationResource($reservation)
        ];

        return response()->json($res);
    }
}
