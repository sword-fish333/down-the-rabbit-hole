<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('frontend.home.index');
    }
}
