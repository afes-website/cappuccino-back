<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Guest extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = 'registered_at';

    const UPDATED_AT = null;

    const VALID_CHARACTER = '0123456789ABCDEFabcdef';
    const ID_LENGTH = 5;
    const PREFIX_LENGTH = 2;
    const VALID_FORMAT = '/\A[A-Za-z]{2}-[0-9A-Fa-f]{5}\Z/';

    public static function findOrFail(string $id, $http_code = 404) {
        $guest = self::find($id);
        if (!$guest) {
            Log::notice('GUEST_NOT_FOUND', ['guest_id' => $id]);
            abort($http_code, 'GUEST_NOT_FOUND');
        }
        return $guest;
    }

    public static function calculateParity(string $id_sub): string {
        $digits = [];
        foreach (str_split($id_sub) as $char) {
            $digits[] = hexdec($char);
        }
        $parity_digits = ($digits[0] + $digits[1] * 3 + $digits[2] + $digits[3] * 3) % 0x10;
        return strtoupper(dechex($parity_digits));
    }

    public static function validate(string $guest_id): bool {
        if (!preg_match(self::VALID_FORMAT, $guest_id)) return false;
        $guest_id = strtoupper($guest_id);
        return self::calculateParity(substr($guest_id, 3, 4)) === $guest_id[-1];
    }

    public static function assertCanBeRegistered(string $guest_id, string $guest_type): void {
        if (!self::validate($guest_id)) {
            Log::notice('INVALID_WRISTBAND_CODE', ['guest_id' => $guest_id]);
            abort(400, 'INVALID_WRISTBAND_CODE');
        }
        if (strpos($guest_id, config('cappuccino.guest_types')[$guest_type]['prefix']) !== 0) {
            Log::notice('WRONG_WRISTBAND_COLOR', ['guest_id' => $guest_id, 'type' => $guest_type]);
            abort(400, 'WRONG_WRISTBAND_COLOR');
        }
        if (self::find($guest_id)) {
            Log::notice('ALREADY_USED_WRISTBAND', ['guest_id' => $guest_id]);
            abort(400, 'ALREADY_USED_WRISTBAND');
        }
    }

    public function updateLocation() {
        $latest_log = $this->logs()->whereNotNull('exhibition_id')->orderByDesc('timestamp')->first();
        if (!$latest_log) return;
        $exh_id = $latest_log->exhibition_id;
        $log_type = $latest_log->log_type;

        switch ($log_type) {
            case 'enter':
                $this->update(['exhibition_id' => $exh_id]);
                break;
            case 'exit':
                $this->update(['exhibition_id' => null]);
                break;
        }
    }

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
