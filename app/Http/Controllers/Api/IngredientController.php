<?php

namespace App\Http\Controllers\Api;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IngredientController extends Controller
{
    public function getIngredient(Request $request){
        
        $categoryId = $request->query('category');
        $option = $request->query('option');
        
        
        
        if ($categoryId !== null && $categoryId !== 0) {
            $query = Ingredient::where('type', 'like', '%' . '"' . $categoryId . '"' . '%');
            if ($option == 'yes') {
                $query->where('option', true);
            }else {
                $query->where('option', false);
            }
            $ingredients = $query->get();
        }else{
            $ingredients = [];
        }
        
        


        return response()->json([
            'success' => true,
            'results' => $ingredients,
        ]);
    }
}
