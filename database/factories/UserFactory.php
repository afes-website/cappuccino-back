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
            "perm_reservation" => false,
            "perm_executive" => false,
            "perm_exhibition" => false,
            'perm_teacher' => false,
        ];
    }

    public function permission($perm) {
        $roles = [
            'admin' => 'perm_admin',
            'reservation' => 'perm_reservation',
            'executive' => 'perm_executive',
            'exhibition' => 'perm_exhibition',
            'teacher' => 'perm_teacher'
        ];

        return $this->state(function () use ($roles, $perm) {
            return [
                $roles[$perm] => true,
            ];
        });
    }
}
