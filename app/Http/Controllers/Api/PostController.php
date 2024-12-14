<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function index(Request $request)
    {

        $path = $request->query('path');
       // $path = intval($path);

        $query = Post::where('visible', 1);

        if ($path !== null) {
            $query = $query->where('path', $path);
        } 
        
        $posts = $query->where('archived', 0)->orderBy('order', 'desc')->get();
        
        return response()->json([
            'success'   => true,
            'results'   => $posts,
        ]);
    }
    public function postHome()
    {      
        $posts = Post::where('promo', 1)->get();
        
        return response()->json([
            'success'   => true,
            'results'   => $posts,
        ]);
    }
}
