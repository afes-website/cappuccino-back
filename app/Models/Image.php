<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Image extends Model {

    use HasFactory;

    protected $guarded = [];

    public static function findOrFail(string $id, $http_code = 404) {
        $image = self::find($id);
        if (!$image) {
            Log::info('IMAGE_NOT_FOUND', ['image_id' => $id]);
            abort($http_code, 'IMAGE_NOT_FOUND');
        }
        return $image;
    }

    public static function imageCreate($content, $user_id, $mime_type) {
        $content_medium = \Intervention\Image\Facades\Image::make($content);
        $content_small = \Intervention\Image\Facades\Image::make($content);
        $upsize_callback = function ($constraint) {
            $constraint->upsize();
        };

        if ($content_medium->width() >= $content_small->height()) {
            $content_medium->widen(1440, $upsize_callback);
            $content_small->widen(400, $upsize_callback);
        } else {
            $content_medium->heighten(1440, $upsize_callback);
            $content_small->heighten(400, $upsize_callback);
        }

        $id = Str::random(40);

        return self::create([
            'id' => $id,
            'content' => $content_medium->encode($mime_type),
            'content_small' => $content_small->encode($mime_type),
            'user_id' => $user_id,
            'mime_type' => $mime_type
        ]);
    }

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;
}
