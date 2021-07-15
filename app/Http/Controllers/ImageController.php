<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImageController extends Controller {
    public function show(Request $request, $id) {
        $image = Image::find($id);
        if (!$image) abort(404);
        $size = $request->query('size');

        return response($size === 's' ? $image->content_small : $image->content)
            ->header('Content-Type', $image->mime_type)
            ->header('Last-Modified', $image->created_at->toRfc7231String());
    }

    public function create(Request $request) {
        if (!$request->hasFile('content')) abort(400, 'file is not uploaded');
        $file = $request->file('content');
        $mime_type = $file->getMimeType();
        if (substr($mime_type, 0, 6) !== 'image/') abort(400, 'uploaded file is not an image');

        $content_medium = \Intervention\Image\Facades\Image::make($file->get());
        $content_small = \Intervention\Image\Facades\Image::make($file->get());

        if ($content_medium->width() >= $content_small->height()) {
            $content_medium->widen(1440, function ($constraint) {
                $constraint->upsize();
            });
            $content_small->widen(400, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $content_medium->heighten(1440, function ($constraint) {
                $constraint->upsize();
            });
            $content_small->heighten(400, function ($constraint) {
                $constraint->upsize();
            });
        }

        $id = Str::random(40);

        Image::create([
            'id' => $id,
            'content' => $content_medium->encode($mime_type),
            'content_small' => $content_small->encode($mime_type),
            'user_id' => $request->user()->id,
            'mime_type' => $mime_type
        ]);

        return response()->json(['id' => $id], 201);
    }
}
