<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'id' => $this->faker->realText(16),
            'name' => $this->faker->name,
            'password' => Hash::make($this->faker->password),
            "perm_admin" => false,
            "perm_blogAdmin" => false,
            "perm_blogWriter" => false,
            "perm_exhibition" => false,
            "perm_general" => false,
            "perm_reservation" => false,
            'perm_teacher' => false,
        ];
    }

    public function permission($perm) {
        $roles = [
            'admin' => 'perm_admin',
            'blogAdmin' => 'perm_blogAdmin',
            'blogWriter' => 'perm_blogWriter',
            'exhibition' => 'perm_exhibition',
            'general' => 'perm_general',
            'reservation' => 'perm_reservation',
            'teacher' => 'perm_teacher'
        ];

        return $this->state(function () use ($roles, $perm) {
            return [
                $roles[$perm] => true,
            ];
        });
    }
}
