<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function index()
    {
        return Inertia::render('app');
    }
}
