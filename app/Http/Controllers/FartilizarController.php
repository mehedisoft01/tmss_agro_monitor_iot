<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FartilizarController extends Controller
{
    public function fartilizerCard()
    {
        return view('fartilizer.fartilizarCard');
    }
}
