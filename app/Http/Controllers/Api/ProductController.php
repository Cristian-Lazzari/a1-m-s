<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $categoryId = $request->query('category');
        $query = Product::with('category', 'ingredients');
        $from = $request->query('from');

        if($from !== null){

            if($from !== 'menu'){  
                $query = $query->where('visible', 1);
            }
        }else{
            $query = $query->where('visible', 1);
        }

        if ($categoryId !== null && $categoryId !== 0 && $categoryId !== '0') {
            $query = $query->where('category_id', $categoryId);
        } 
        
        $products = $query->where('archived', 0)->get();
        
        return response()->json([
            'success'   => true,
            'results'   => $products,
            'allergens'   => config('configurazione.allergens'),
        ]);
    }

    public function promoHome()
    {
        
        $products = Product::with('category', 'ingredients')->where('promotion', 1)->orderBy('updated_at')->get();
        
        return response()->json([
            'success'   => true,
            'results'   => $products,
        ]);
    }
}
