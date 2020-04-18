<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use Csv;
use DB;
use Request;
use Auth;

class CoinsController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function alert()
    {
    	\Alerts::alert();
    }

    public function index()
    {
        $alerts = \App\Alert::all();

        return view('alerts.index', ['alerts' => $alerts]);
    }

    public function show()
    {

    }

    public function add()
    {
        $tickers = \Tickers::get(500000);

        return view('alerts/add', ['tickers' => $tickers]);
    }

    public function edit($id)
    {
        $alerts = \App\Alert::all();
        $currentAlert = \App\Alert::find($id);

        if (! $currentAlert) {
            return redirect()->route('alerts');
        }

        $tickers = \Tickers::get(500000);

        // Desc sort
        usort($tickers,function($first,$second){
            return $first->volume < $second->volume;
        });

        return view('alerts/edit', ['alerts' => $alerts, 'tickers' => $tickers, 'currentAlert' => $currentAlert]);
        
    }

    public function store($id = null)
    {
        if ($id) {
            $alert = \App\Alert::find($id);
        } else {
            $alert = new \App\Alert;
        }
        
        $alert->ticker = request('ticker');
        $alert->price = request('price');
        $alert->comment = request('comment');
        $alert->direction = request('direction');
        $alert->save();

        return redirect()->route('alerts');
    }

    public function delete($id)
    {
        \App\Alert::find($id)->delete();

        return redirect()->route('alerts');
    }
}