<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class IngredientController extends Controller
{
    private $validations_ingredient = [
        'name_ing'          => 'required|string|min:2|unique:ingredients,name',
        'price_ing'         => 'required',
    ];
    private $validations_ingredient1 = [
        'name_ing'          => 'required|string|min:2',
        'price_ing'         => 'required',
    ];
    public function index()
    {
        $ingredients    = Ingredient::where('option', false)->orderBy('name')->get(); 
        $options        = Ingredient::where('option', true)->orderBy('name')->get(); 
        return view('admin.Ingredients.index', compact('ingredients', 'options'));
    }
     
    public function create()
    {
    $categories     = Category::all();
    return view('admin.Ingredients.create', compact('categories'));
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $request->validate($this->validations_ingredient);
        
        $prezzo_stringa = str_replace(',', '.', $data['price_ing']);
        $prezzo_stringa = preg_replace('/[^0-9.]/', '', $prezzo_stringa);
        $prezzo_float = floatval($prezzo_stringa);
        if (isset($data['allergens_ing'])){
            $ingredient_allergens = $data['allergens_ing'];
        }else{
            $ingredient_allergens = '[]';
        }

        if (isset($data['type_ing'])){
            $type_ing = $data['type_ing'];
        }else{
            $type_ing = '[]';
        }
        
        $new_ing = new Ingredient();
        if (isset($data['image_ing'])) {
            $imagePath = Storage::put('public/uploads', $data['image_ing']);
            $new_ing->icon = $imagePath;
        }
        $new_ing->name = $data['name_ing'];

        if (isset($data['option_ing'])) {
            $new_ing->option = true;
        }else{
            $new_ing->option = false;
        }

        $new_ing->price = intval(round($prezzo_float * 100));
        $new_ing->type = json_encode($type_ing);

         if($ingredient_allergens !== '[]'){
            $rightall = array_map('intval', array_values($ingredient_allergens));
            $new_ing->allergens = json_encode($rightall);
        }else{
            $new_ing->allergens = '[]';
        }
        $new_ing->save();
        
        $m = ' "' . $new_ing['name'] . '" è stato creato correttamente';
        return to_route('admin.ingredients.index')->with('ingredient_success', $m);   
    }
    
    
    public function edit($id)
    {
        $ingredient    = Ingredient::where('id', $id)->firstOrFail();
        $categories    = Category::all();
        return view('admin.Ingredients.edit', compact('categories', 'ingredient'));
    }
    protected function cleanArray($array) {
        $hasGluten = false;
        $hasNoGluten = false;
        foreach ($array as $item) {     
            if ($item == 1) {
                $hasGluten = true;
            } elseif ($item == 4) {
                $hasNoGluten = true;
            }     
        } 
        if ($hasGluten && $hasNoGluten) {
            $filteredArray = array_filter($array, function($value) {
                return $value !== 1;
            });
        }else{
            $filteredArray = $array;
        }  
        return array_values($filteredArray); // re-indicizzare l'array
    }
    public function update(Request $request, $id)
    {
        //funzione del cazzo di chat per controllare la questione glutine e senza glutine
        
        //end---funzione del cazzo di chat per controllare la questione glutine e senza glutine
        
        $data = $request->all();
        $request->validate($this->validations_ingredient1);
        
        $prezzo_stringa = str_replace(',', '.', $data['price_ing']);
        $prezzo_stringa = preg_replace('/[^0-9.]/', '', $prezzo_stringa);
        $prezzo_float = floatval($prezzo_stringa);
        if (isset($data['allergens_ing'])){
            $ingredient_allergens = $data['allergens_ing'];
        }else{
            $ingredient_allergens = '[]';
        }
        if (isset($data['type_ing'])){
            $type_ing = $data['type_ing'];
        }else{
            $type_ing = '[]';
        }
        
        $new_ing = Ingredient::where('id', $id)->firstOrFail();
        $new_ing->name = $data['name_ing'];
        if (isset($data['image_ing'])) {
            $imagePath = Storage::put('public/uploads', $data['image_ing']);
            if ($new_ing->icon) {
                Storage::delete($new_ing->icon);
            }
            $new_ing->icon = $imagePath;
        }
        if (isset($data['option_ing'])) {
            $new_ing->option = true;
        }else{
            $new_ing->option = false;
        }
        $new_ing->price = intval(round($prezzo_float * 100));
        $new_ing->type = json_encode($type_ing);
    
        if($ingredient_allergens !== '[]'){
            $rightall = array_map('intval', array_values($ingredient_allergens));
            $new_ing->allergens = json_encode($rightall);
            $id_ing = $new_ing->id;
            $prodotti = Product::whereHas('ingredients', function($query) use ($id_ing) {
                $query->where('ingredient_id', $id_ing);
            })->get();
            foreach ($prodotti as $p) {
                $allergens = json_decode($p['allergens'], 1);
                foreach ($rightall as $a) {
                    if(in_array($a, $allergens)){
                        $allergens[] = $a;
                    }
                }
                $cleanallergens = $this->cleanArray($allergens);
                $p->allergens = json_encode($cleanallergens); 
                $p->update(); 
            }
        }else{
            $new_ing->allergens = '[]';
        }
        $new_ing->update();
        
        //dd($prodotti);
        
        
        
        $m = ' "' . $new_ing['name'] . '" è stato modificato correttamente';
        return to_route('admin.ingredients.index')->with('ingredient_success', $m);
 
    }
    
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->products()->detach();
        $ingredient->delete();
        $m = ' "' . $ingredient->name . '" è stato eliminato e rimosso dai prodotti correttamente';
        return to_route('admin.ingredients.index')->with('delete_success', $m);
    }
    public function show($id)
    {
        $ingredient    = Ingredient::where('id', $id)->firstOrFail();; 
        $categories    = Category::all();
        return view('admin.Ingredients.show', compact('categories', 'ingredient'));
    }
     
   
    

    
}
    