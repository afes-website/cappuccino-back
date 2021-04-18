<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibition extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    public function guests() {
        return $this->hasMany('\App\Models\Guest');
    }

    public function logs() {
        return $this->hasMany('\App\Models\ActivityLog', 'exhibition_id');
    }

    public function user() {
        return $this->belongsTo('\App\Models\User', 'id');
    }

    public function countGuest() {
        $terms = Term::all();
        $res = [];
        foreach ($terms as $term) {
            $guest = $this
                ->guests()
                ->where('term_id', $term->id)
                ->where('exited_at', '!=', null)
                ->get();
            $count = $guest->count();
            if ($count==0) continue;
            $res[$term->id] = $count;
        }
        return $res;
    }
}
