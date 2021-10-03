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
            'is_spare' => $this->is_spare,
            'registered_at' => $this->registered_at,
            'revoked_at' => $this->revoked_at,
            'exhibition_id' =>$this->exhibition_id,
        ];
    }
}
