@extends('layouts.base')

@section('contents')
@php
  //se impostato a true gli ordini vengono presi in base ai pezzi altrimenti in base al numero di ordini
$domain = 'https://future-plus.it/allergens/';
 
@endphp
 
<a class="my_btn_5 ml-auto" href="{{ route('admin.products.index') }}">Tutti i prodotti</a>
<h1>Dettagli prodotto</h1>
<div class="show_p">
    <h2>
        @if ($product->promotion)
        <svg height="24px" class="promotion_on" version="1.2" viewBox="0 0 24 24" width="24px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><g><path d="M9.362,9.158c0,0-3.16,0.35-5.268,0.584c-0.19,0.023-0.358,0.15-0.421,0.343s0,0.394,0.14,0.521    c1.566,1.429,3.919,3.569,3.919,3.569c-0.002,0-0.646,3.113-1.074,5.19c-0.036,0.188,0.032,0.387,0.196,0.506    c0.163,0.119,0.373,0.121,0.538,0.028c1.844-1.048,4.606-2.624,4.606-2.624s2.763,1.576,4.604,2.625    c0.168,0.092,0.378,0.09,0.541-0.029c0.164-0.119,0.232-0.318,0.195-0.505c-0.428-2.078-1.071-5.191-1.071-5.191    s2.353-2.14,3.919-3.566c0.14-0.131,0.202-0.332,0.14-0.524s-0.23-0.319-0.42-0.341c-2.108-0.236-5.269-0.586-5.269-0.586    s-1.31-2.898-2.183-4.83c-0.082-0.173-0.254-0.294-0.456-0.294s-0.375,0.122-0.453,0.294C10.671,6.26,9.362,9.158,9.362,9.158z"></path></g></g></svg>
        @endif
        {{$product->name}}</h2>
    <div class="split_p">
        <div class="image_p">
            @if (isset($product->image))
                <img class="logo_" src="{{ asset('public/storage/' . $product->image) }}" alt="{{$product->name }}">
            @else 
                <img class="logo_" src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$product->name }}">
            @endif 
            <div class="allergens">
                @if ($product->allergens !== [])
                    @php $all = json_decode($product->allergens) @endphp
                    @foreach ($all as $p)
                        <img src="{{config('configurazione.allergens')[$p]['img']}}" alt="{{config('configurazione.allergens')[$p]['name']}}" title="{{config('configurazione.allergens')[$p]['name']}}">
                    @endforeach
                @endif
            </div>
        </div>
        <div class="info">
            <section>
                <h4>Ingredienti:</h4>
                <p>
                    @foreach ($product->ingredients as $ingredient)     
                    {{ $ingredient->name }}{{ !$loop->last ? ', ' : '.' }}
                    @endforeach
                    @if (count($product->ingredients))
                <p>{{$product->description}}</p> @else <p>(non assegnati)</p> @endif
                </p>
            </section>
            <section>
                <h4>Descrizione:</h4>
                @if ($product->description)
                <p>{{$product->description}}</p> @else <p>(nessuna)</p> @endif
            </section>
            <section>
                <h4>Categoria:</h4>
                <p>{{$product->category->name}}</p>
            </section>
        </div>
    </div>
    <div class="old-price">€{{$product->old_price / 100}}</div>
    <div class="price">€{{$product->price / 100}}</div>
    <div class="prod-spec">
        <div class="set_plate">
            <div class="title">
                @if (config('configurazione.typeOfOrdering') && config('configurazione.pack') > 2)
                <p>Slot piatto:</p>
                <p>Tipo piatto:</p>
                @endif
                <p>Costum cliente:</p>
            </div>
            <div class="res">
                @if (config('configurazione.typeOfOrdering') && config('configurazione.pack') > 2)
                    <p>{{$product->slot_plate}}</p>
                    @if($product->type_plate == 0) <p>Altro</p> @elseif($product->type_plate == 1) <p>Cucina 1</p> @else <p>Cucina 2</p> @endif
                @endif
                @if($product->tag_set == 0)
                <p> Nessuna </p>  @elseif($product->tag_set == 1)
                <p> Togliere </p> @elseif($product->tag_set == 2)
                <p> Aggiungiere </p>
                @else
                <p> Aggiungiere / Togliere</p>
                @endif
            </div>
        </div>
       
        <div class="actions">
            <form action="{{ route('admin.products.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="1">
                <input type="hidden" name="a" value="0">
                <input type="hidden" name="id" value="{{$product->id}}">
                @if (!$product->visible)
                    <button class="my_btn_2 op" type="submit">
                        <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
                            <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z"/>
                            <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z"/>
                        </svg>  
                    </button>
                @else
                    <button class="my_btn_2" type="submit">
                        <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/>
                        </svg>    
                    </button>
                @endif
                
            </form>
            <a class="my_btn_1 " href="{{ route('admin.products.edit', $product) }}">Modifica</a>      
            <form action="{{ route('admin.products.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="0">
                <input type="hidden" name="a" value="1">
                <input type="hidden" name="id" value="{{$product->id}}">
                <button class="my_btn_3" type="submit">{{$product->archived == 1 ? 'Ripristina': 'Archivia'}}</button>
            </form>
            
            <form action="{{ route('admin.products.destroy', ['product'=>$product]) }}" method="post" >
                @method('delete')
                @csrf
                <input type="hidden" name="f" value="1">
                <button class="my_btn_2 bg-danger" type="submit">Elimina</button>
            </form>
           
        </div>
    </div>
    <p>Data creazione: {{$product->created_at}}, Ultima modifica: {{$product->updated_at}}</p>
</div>

 

@endsection