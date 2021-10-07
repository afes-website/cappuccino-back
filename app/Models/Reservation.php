<?php

namespace App\Models;

use App\Exceptions\HttpExceptionWithErrorCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Reservation extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'term_id', 'member_all',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    public static function findOrFail(string $id, $http_code = 404) {
        $reservation = self::find($id);
        if (!$reservation) throw new HttpExceptionWithErrorCode($http_code, 'RESERVATION_NOT_FOUND');
        return $reservation;
    }

    public function guest() {
        return $this->hasMany(Guest::class);
    }

    public function term() {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return string|null ErrorCode (null if there is no problem)
     */
    public function getErrorCode() {
        $term = $this->term;
        $current = Carbon::now();

        if ($term->enter_scheduled_time >= $current || $term->exit_scheduled_time < $current) {
            return 'OUT_OF_RESERVATION_TIME';
        }

        if ($this->guest()->count() >= $this->member_all) {
            return 'ALL_MEMBER_CHECKED_IN';
        }

        return null;
    }

    public function revokeAllGuests() {
        $modified = self::guest()->whereNull('revoked_at')->update([
            'revoked_at' => Carbon::now(),
            'is_force_revoked' => true
        ]);
        if ($modified) {
            Log::info("{$modified} guest has revoked.");
        }
    }
}
