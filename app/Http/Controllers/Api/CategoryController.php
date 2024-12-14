<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::where('id', '!=', 1)->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'results' => $categories,
        ]);
    }
}
