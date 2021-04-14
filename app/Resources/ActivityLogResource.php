<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->toIso8601ZuluString(),
            'guest' => new GuestResource($this->guest),
            'exh_id' => $this->exh_id,
            'log_type' => $this->log_type
        ];
    }
}
