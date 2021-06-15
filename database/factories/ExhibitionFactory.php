<?php

namespace Database\Factories;

use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExhibitionFactory extends Factory {

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Exhibition::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'id'=>User::factory(),
            'name'=>function (array $attributes) {
                return User::find($attributes['id'])->name;
            },
            'room_id'=>$this->faker->userName,
            'capacity'=> $this->faker->numberBetween(1, 100),
            'updated_at'=>$this->faker->dateTime,
        ];
    }
}
