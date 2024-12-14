<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->default('1'); 
            $table->foreign     ('category_id')->references('id')->on('categories'); 

            $table->string      ('name', 100);
            $table->bigInteger  ('price')->default(0);
            $table->bigInteger  ('old_price')->nullable();
            $table->string      ('image')->nullable();
            $table->text        ('description')->nullable(); 
            
            $table->text        ('allergens')->nullable(); 
            
            $table->tinyInteger ('slot_plate')->nullable(); //indica quanti slot occupa
            $table->tinyInteger ('type_plate')->nullable();  //indica se è di tipo fq ft o altro 
            $table->tinyInteger ('tag_set')->nullable(); //indica se questo prodotto puo subire modifiche 0 nessuna, 1 togli ingredienti, 2 aggiungi e togli
            $table->boolean     ('visible')->default(true);
            $table->boolean     ('archived')->default(false);
            $table->boolean     ('promotion')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
