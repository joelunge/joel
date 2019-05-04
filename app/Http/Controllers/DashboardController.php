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

        foreach ($tickers as $key => $ticker) {
            if ($ticker->volume < 100000) {
                $ticker->formattedVolume = round($ticker->volume);
            } elseif ($ticker->volume >= 100000 && $ticker->volume < 1000000) {
                $ticker->formattedVolume = round($ticker->volume / 1000).'K';
            } elseif ($ticker->volume >= 1000000 && $ticker->volume < 1000000000) {
                $ticker->formattedVolume = round($ticker->volume / 1000000).'M';
            } elseif ($ticker->volume >= 1000000000) {
                $ticker->formattedVolume = round($ticker->volume / 1000000000).'B';
            } else {
                $ticker->formattedVolume = round($ticker->volume);
            }
        }

        return view('dashboard', ['tickers' => $tickers]);
    }
}
