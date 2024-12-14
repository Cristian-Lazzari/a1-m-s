<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IngredientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ingredients = [
            [
                'name' => 'impasto celiaco',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[12]",
                'option' => true,
            ],
            [
                'name' => 'pomodoro',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => true,
            ],

            [
                'name' => 'pesto della casa',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'melanzane grigliate',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'salsa di peperoncini freschi',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'passata di pomodoro cotta con ventricina piccante',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'aglio',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'basilico',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'rucola',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'foglie del cappero',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'carciofini',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'funghi',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'olive nere',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'patate',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'cipolla',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'radicchio',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'verdure di sIngredientione',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'origano',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'rosmarino',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'salsa tartufata',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pesto di basilico',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pomodorino ciliegino',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pomodorino giallo',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pinoli',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pesto di pistacchi della casa',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'granella di pistacchi',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'noci',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'carne salada trentina',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'salsiccia',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'salame',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'mortadella',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'ventricina piccante',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'arrosto di tacchino in porchetta marchigiano',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'würstel',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pancetta marchigiana',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'speck',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'parmigiano',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'mozzarella fior di latte',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'mozzarella di bufala campana',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'scaglie di grana',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'stracciatella',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'stracciatella di burrata',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'gorgonzola',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pecorino',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'acciughe del mar cantabrico',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'cotto',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],

            [
                'name' => 'pancetta',  
                'price' => 100,
                'type' => "[1,2,3,4,5]",
                'allergens' => "[1,2,3]",
                'option' => false,
            ],


        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}

