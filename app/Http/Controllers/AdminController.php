<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function login()
    {
        return view('admin.login');
    }

    public function authenticate() {
        $attributes = request()->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (auth()->attempt($attributes)) {
            return redirect('/admin');
        } else {
            return back()->withErrors(['email' => 'Your provided credentials could not be verified.']);
        }
    }

    public function kofi() {
        return view('admin.kofi');
    }
}
