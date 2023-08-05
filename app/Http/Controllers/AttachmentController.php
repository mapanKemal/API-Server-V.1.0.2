<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class AttachmentController extends Controller
{
    //
    public function OFuploadImage($requestFile, $path2 = "", $extension)
    {
        $filename  = md5(uniqid() . '_' . $path2 . '_' . time()) . '.' . $extension;

        $pathParent = 'OnlineForm/';
        $pathChildren = $path2 . '/';
        $destinationPath = public_path('/Image/' . $pathParent . $pathChildren);
        !is_dir($destinationPath) &&
            mkdir($destinationPath, 0777, true);

        /* Save image quality 60% */
        try {
            $img = Image::make($requestFile->getRealPath())
                ->save($destinationPath . $filename, 60);
            return response([
                "filename" => $filename,
                "ext" => $extension,
                "size" => $img->filesize(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
