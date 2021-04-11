<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public function guest() {
        return $this->belongsTo('\App\Models\Guest');
    }

    public function term() {
        return $this->belongsTo('\App\Models\Term');
    }

    public function getErrorCode() {
        $term = $this->term;
        $current = Carbon::now();

        if (new Carbon($term->enter_scheduled_time) >= $current
            || new Carbon($term->exit_scheduled_time) < $current
        ) {
            return 'OUT_OF_RESERVATION_TIME';
        }

        if ($this->guest_id !== null) {
            return 'ALREADY_ENTERED_RESERVATION';
        }

        return null;
    }
}
