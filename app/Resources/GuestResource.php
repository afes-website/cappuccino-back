<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'term' => new TermResource($this->term),
            'entered_at' => $this->entered_at,
            'exited_at' => $this->exited_at,
            'exh_id' =>$this->exh_id,
        ];
    }
}
