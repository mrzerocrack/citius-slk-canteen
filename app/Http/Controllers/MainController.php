<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\CardTapBroadcastEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class MainController extends Controller
{
    public function get_data_tap_from_device(Request $request){
        $response = Http::post('http://ipp-kalteng1.com/post-mifare-canteen', [
            'card_id' => $request->card_id,
            'canteen_name' => $request->canteen_name,
            'time_category' => (isset($request->time_category) ? (int)$request->time_category :""),
        ]);
        $response = json_decode($response);
        $response->canteen_name = str_replace("canteen_", "", $request->canteen_name);
        CardTapBroadcastEvent::dispatch($response);
        return $response;
    }
}
