<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    private $validations_category = [
        'name'          => 'required|string|min:2|unique:categories,name',
    ];
    private $validations_category1 = [
        'name'          => 'required|string|min:2',
    ];
    public function index()
    { 
        $categories     = Category::all();
        return view('admin.Categories.index', compact('categories'));
    }
     
    public function create()
    {
        return view('admin.Categories.create');
    }


    public function store(Request $request)
    {
        $data = $request->all();
        $request->validate($this->validations_category);
        
        $category = new Category();
        $category->name = $data['name'];
        if (isset($data['icon'])) {
            $iconPath = Storage::put('public/uploads', $data['icon']);
            $category->icon = $iconPath;
        } 
        $category->save();
        
        $m = ' "' . $category['name'] . '" è stato creato correttamente';
        return to_route('admin.categories.index')->with('category_success', $m);   
    }
    
    
    public function edit($id)
    {
        $category    = Category::where('id', $id)->firstOrFail();; 
    
        return view('admin.Categories.edit', compact( 'category'));
    }
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $request->validate($this->validations_category1);
        
        $category = Category::where('id', $id)->firstOrFail();
        $category->name = $data['name'];
        if (isset($data['icon'])) {
            $iconPath = Storage::put('public/uploads', $data['icon']);
            if ($category->icon) {
                Storage::delete($category->icon);
            }
            $category->icon = $iconPath;
        }
        $category->update();
        
        $m = ' "' . $category['name'] . '" è stato creato correttamente';
        return to_route('admin.categories.index')->with('category_success', $m);
 
    }
    
    public function destroy(Category $category)
    {
        foreach ($category->product as $product) {
            $product->category_id = 1;
            $product->update();
        }
        
        $category->delete();
        $m = ' "' . $category->name . '" è stata eliminato e rimossa dai prodotti correttamente';
        return to_route('admin.categories.index')->with('delete_success', $m);
    }
    public function show($id)
    {
        $category    = category::where('id', $id)->firstOrFail();
        //return view('admin.Categories.show', compact('category'));
    }  
}
