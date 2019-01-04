<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use H;
use Csv;
use DB;
use Request;
use Auth;

class NewsController extends Controller
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

    public function list()
    {
    	echo "Hej"; exit;
    	$news = $this->getNews();

        return view('news.list', ['news' => $news]);
    }

    private function getNews($coin = false, $userId = false)
    {
    	$news = App\New::where('id', 1);
    }

  //   private function getNews($coin = false, $userId = false)
  //   {
  //   	$news = App\New;
  //   	if ($coin) {
  //   		$news = $news->where('coin', '=', $coin);
  //   	}
  //   	$news = $news->orderBy('updated', 'ASC')
		// 	->get()
  //           ->toArray();

		// return $news;
  //   }
}