<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogEntryResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->toIso8601String(),
            'guest' => new GuestResource($this->guest),
            'exhibition_id' => $this->exhibition_id,
            'log_type' => $this->log_type
        ];
    }
}
