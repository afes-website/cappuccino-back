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
            // Upload Size, Medium Size excepted, Small size excepted
            '1440x900'  => [[1440, 900], [1440, 900], [400, 250]],
            'small' => [[200, 200], [200, 200], [200, 200]],
            '500x500' => [[500, 500], [500, 500], [400, 400]],
            'wide' => [[2880, 900], [1440, 450], [400, 125]],
            'tall' => [[900, 2880], [450, 1440], [125, 400]]
        ];
    }

    /**
     * upload のテスト
     * @dataProvider imageProvider
     */
    public function testUpload($upload) {
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
     * LoginしてないときはUploadができない
     */
    public function testUploadWithoutLogin() {
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
     * Image でないものはアップロードできない
     */
    public function testUploadNonImage() {
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
     * Download / リサイズのテスト
     * @dataProvider imageProvider
     */
    public function testDownload($upload, $medium, $small) {
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

    public function testNotFound() {
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
        $this->assertResponseStatus(404);
    }
}
