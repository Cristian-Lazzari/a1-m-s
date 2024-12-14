<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();

            $table->string      ('name', 100);
            $table->bigInteger  ('price');
            $table->text        ('type'); //indica se deve essere possibile aggiungierlo in una o piu categorie
            $table->text        ('allergens')->nullable() ;
            $table->string      ('icon')->nullable();
            $table->boolean     ('option'); //indica se  e' un ingrediente o un opzione
            
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('ingredients');
    }
};
