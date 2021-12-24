<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


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
}
