<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Storage;


class createController extends Controller
{
    //
    public function startCreate(REQUEST $request){
        dump($request);
        return 0;
    }
    public function startDelete(REQUEST $request){
        dump($request);
        return 1;
    }

    //画像保存　こちらで保存したPATHを返す
    public function createPict(REQUEST $request){
        if($request->pict->isValid()){
/*             dump($request->pict);
            dump($request->name);
            dump($request->mimeType);
 */        }
        $image=$request->file('pict');
        dump($image);
        $responce=Storage::disk('s3')->put('/pict',$image,'public-read');
        return $responce;
    }
}
