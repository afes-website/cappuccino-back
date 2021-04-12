<?php

namespace App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {

    /**
     * リソースを配列へ変換する
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => [
                'admin'       => $this->perm_admin       == 1,
                'exhibition'  => $this->perm_exhibition  == 1,
                'executive'   => $this->perm_executive   == 1,
                'reservation' => $this->perm_reservation == 1,
                'teacher'     => $this->perm_teacher     == 1,
            ],
        ];
    }
}
