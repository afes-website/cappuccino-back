<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Exhibition extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    public static function findOrFail(string $id, $http_code = 404) {
        $exhibition = self::find($id);
        if (!$exhibition) abort($http_code, 'EXHIBITION_NOT_FOUND');
        return $exhibition;
    }

    public function guests() {
        return $this->hasMany(Guest::class);
    }

    public function logs() {
        return $this->hasMany(ActivityLogEntry::class, 'exhibition_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'id');
    }

    public function countGuest() {
        return $this
            ->guests()
            ->whereNull('revoked_at')
            ->select('term_id', DB::raw('count(1) as cnt'))
            ->groupBy('term_id')
            ->pluck('cnt', 'term_id');
    }
}
