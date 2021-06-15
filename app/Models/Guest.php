<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = 'entered_at';

    const UPDATED_AT = null;

    const VALID_CHARACTER = '234578acdefghijkmnprstuvwxyz';
    const ID_LENGTH = 5;
    const VALID_FORMAT = '/\A[A-Z]{2,3}-[2-578ac-kmnpr-z]{5}\Z/';

    public function reservation() {
        return $this->belongsTo(Reservation::class);
    }

    public function logs() {
        return $this->hasMany(ActivityLogEntry::class);
    }

    public function term() {
        return $this->belongsTo(Term::class);
    }
}
