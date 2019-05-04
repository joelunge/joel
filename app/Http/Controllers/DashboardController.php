<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tickers = \Tickers::get();

        // Desc sort
        usort($tickers,function($first,$second){
            return $first->volume < $second->volume;
        });

        return view('dashboard', ['tickers' => $tickers]);
    }
}
