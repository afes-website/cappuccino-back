<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = true;

    public $timestamps = true;

    const CREATED_AT = 'timestamp';

    const UPDATED_AT = null;

    public function guest() {
        return $this->belongsTo('\App\Models\Guest');
    }

    public function exhibition() {
        return $this->belongsTo('\App\Models\Exhibition');
    }
}
