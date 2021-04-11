<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = 'entered_at';

    const UPDATED_AT = null;

    public function reservation() {
        return $this->belongsTo('\App\Models\Reservation');
    }

    public function logs() {
        return $this->hasMany('\App\Models\ActivityLog');
    }

    public function term() {
        return $this->belongsTo('\App\Models\Term');
    }
}
