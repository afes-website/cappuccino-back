<?php

namespace Database\Factories;

use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

class ImageFactory extends Factory {

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
            'id'=>$this->faker->userName,
            'content' => hex2bin(
            // tiny png(1x1px 8bit)
                "89504e470d0a1a0a0000000d49484452".
                "000000010000000108000000003a7e9b".
                "550000000a4944415408d763780e0000".
                "e900e8f07b6a770000000049454e44ae".
                "426082"
            ),
            'content_small' => hex2bin(
            // tiny png(1x1px 8bit)
                "89504e470d0a1a0a0000000d49484452".
                "000000010000000108000000003a7e9b".
                "550000000a4944415408d763780e0000".
                "e900e8f07b6a770000000049454e44ae".
                "426082"
            ),'user_id' => User::factory(),
            'mime_type' => 'image/png'
        ];
    }
}
