<?php
namespace Tests;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * - /images:post
 * - /images/{id}/get
 */
class ImageTest extends TestCase {

    public function imageProvider() {
        return [
            // Upload Size, Medium Size expected, Small size expected
            '1440x900'  => [[1440, 900], [1440, 900], [400, 400]],
            'small' => [[200, 200], [200, 200], [200, 200]],
            '500x500' => [[500, 500], [500, 500], [400, 400]],
            '500x300' => [[500, 300], [500, 300], [300, 300]],
            'wide' => [[2880, 900], [1440, 450], [400, 400]],
            'tall' => [[900, 2880], [450, 1440], [400, 400]]
        ];
    }

    /**
     * post のテスト
     * @dataProvider imageProvider
     */
    public function testPost($upload) {
        $user = User::factory()->create();
        [$width, $height] = $upload;
        $this->actingAs($user)->call(
            'POST',
            '/images',
            [],
            [],
            [
                'content' => UploadedFile::fake()->image('hoge.jpg', $width, $height)
            ]
        );
        $this->assertResponseStatus(201);

        $this->seeJsonStructure(['id']);
    }

    /**
     * Login してないときは post ができない
     */
    public function testPostWithoutLogin() {
        $this->call(
            'POST',
            '/images',
            [],
            [],
            [
                'content' => UploadedFile::fake()->image('hoge.jpg')
            ]
        );
        $this->assertResponseStatus(401);
    }

    /**
     * Image でないものは post できない
     */
    public function testPostNonImage() {
        $writer_user = User::factory()->create();
        $this->actingAs($writer_user)->call(
            'POST',
            '/images',
            [],
            [],
            [
                'content' => UploadedFile::fake()->create('hoge.txt')
            ]
        );
        $this->assertResponseStatus(400);
    }

    /**
     * Get / リサイズのテスト
     * @dataProvider imageProvider
     */
    public function testGet($upload, $medium, $small) {
        $user = User::factory()->create();
        [$width, $height] = $upload;
        $this->actingAs($user)->call(
            'POST',
            '/images',
            [],
            [],
            [
                'content' => UploadedFile::fake()->image('hoge.jpg', $width, $height)
            ]
        );
        $this->assertResponseStatus(201);

        $id = json_decode($this->response->getContent())->id;

        foreach (['m' => $medium, 's' => $small] as $size => [$width, $height]) {
            $this->get("/images/{$id}?size={$size}");
            $this->assertResponseOk();
            $img = Image::make($this->response->getContent());
            $this->assertEquals($width, $img->width());
            $this->assertEquals($height, $img->height());
        }
    }

    public function testGetNotFound() {
        $user = User::factory()->create();
        $this->actingAs($user)->call(
            'POST',
            '/images',
            [],
            [],
            [
                'content' => UploadedFile::fake()->image('hoge.jpg')
            ]
        );
        $this->assertResponseStatus(201);

        $id = Str::random();
        $this->get("/images/$id");
        $this->expectErrorResponse("IMAGE_NOT_FOUND", 404);
    }
}
