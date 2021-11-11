<?php

namespace App\Models;

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
        if (!$reservation) {
            Log::notice('RESERVATION_NOT_FOUND', ['reservation_id' => $id]);
            abort($http_code, 'RESERVATION_NOT_FOUND');
        }
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
            Log::notice('OUT_OF_RESERVATION_TIME', ['reservation_id' => $this->id, 'term_id' => $term->id]);
            return 'OUT_OF_RESERVATION_TIME';
        }

        if ($this->guest()->count() >= $this->member_all) {
            Log::notice('ALL_MEMBER_CHECKED_IN', ['reservation_id' => $this->id]);
            return 'ALL_MEMBER_CHECKED_IN';
        }

        return null;
    }

    public function revokeAllGuests() {
        $modified = self::guest()->whereNull('revoked_at')->update([
            'revoked_at' => Carbon::now(),
            'is_force_revoked' => true,
            'exhibition_id' => null,
        ]);
        if ($modified) {
            Log::info("{$modified} guest has revoked.");
        }
    }
}
