<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'term' => $this->term,
            'people_count' => $this->people_count
        ];
    }
}
