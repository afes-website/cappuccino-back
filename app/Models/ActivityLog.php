<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = true;

    const CREATED_AT = 'timestamp';

    const UPDATED_AT = null;

    public function guest() {
        return $this->belongsTo(Guest::class);
    }

    public function exhibition() {
        return $this->belongsTo(Exhibition::class);
    }
}
