<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id' => Str::random(16),
            'name' => $this->faker->name,
            'password' => Hash::make($this->faker->password),
            'session_key' => Str::random(10),
            "perm_admin" => false,
            "perm_reservation" => false,
            "perm_executive" => false,
            "perm_exhibition" => false,
            'perm_teacher' => false,
        ];
    }

    public function permission(...$permissions) {
        $roles = [
            'admin' => 'perm_admin',
            'reservation' => 'perm_reservation',
            'executive' => 'perm_executive',
            'exhibition' => 'perm_exhibition',
            'teacher' => 'perm_teacher'
        ];

        $states = [];

        foreach ($permissions as $permission) {
            $states[$roles[$permission]] = true;
        }

        return $this->state($states);
    }
}
