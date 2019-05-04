<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use Csv;
use DB;
use Request;
use Auth;

class AlertsController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function rsi15m()
    {
    	\Alerts::alert();
    }
}