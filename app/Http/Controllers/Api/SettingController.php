<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function index() {
        $settings = Setting::all();
        foreach ($settings as $s) {
            $string = json_decode($s['property'], true);  
            $s['property'] = $string;
        }

        return response()->json([
            'success' => true,
            'results' => $settings,
            'double_t'=> config('configurazione.double_t'),
        ]);
    }
}
