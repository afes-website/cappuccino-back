<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model {

    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = false;

    protected $dates = [
        'enter_scheduled_time',
        'exit_scheduled_time',
    ];

    public function getClassAttribute() {
        return config('cappuccino.guest_types')[$this->guest_type]['class'];
    }
}
