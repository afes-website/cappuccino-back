<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ActivityLogFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id' => $this->faker->realText(16),
            'name' => $this->faker->name,
            'password' => Hash::make($this->faker->password),
            "perm_admin" => false,
            "perm_exhibition" => false,
            "perm_general" => false,
            "perm_reservation" => false,
            'perm_teacher' => false,
        ];
    }
}
