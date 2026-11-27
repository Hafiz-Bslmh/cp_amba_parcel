<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function homepage(){
        return view('dashboard.homepage');
    }
    public function pilihanarmada(){
        return view('dashboard.pilihanarmada');
    }
    public function potential_simulation(){
        return view('dashboard.potential_simulation');
    }
}
