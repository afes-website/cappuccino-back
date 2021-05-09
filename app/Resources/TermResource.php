<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TermResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            "id" => $this->id,
            "enter_scheduled_time" => $this->enter_scheduled_time->toIso8601String(),
            "exit_scheduled_time" => $this->exit_scheduled_time->toIso8601String(),
            "guest_type" => $this->guest_type
        ];
    }
}
