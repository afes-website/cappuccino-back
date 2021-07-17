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


        $image = Image::imageCreate($file, $request->user()->id, $mime_type);

        return response()->json(['id' => $image->id], 201);
    }
}
