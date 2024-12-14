@extends('layouts.base')



@section('contents')
@php
  //se impostato a true gli ordini vengono presi in base ai pezzi altrimenti in base al numero di ordini
$domain = 'https://future-plus.it/allergens/';
 
@endphp
<style>
    body{
        background: #020222;      
    }
</style>

@if (session('success'))
    @php
        $data = session('success')
    @endphp
    <div class="alert alert-primary alert-dismissible fade show" role="alert">
        {{ $data }} 
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
 
<a class="my_btn_5 ml-auto" href="{{ route('admin.products.index') }}">Torna ai Prodotti non archiviati</a>

<h1>Prodotti - Archivio</h1>
 
<form class="top-bar-product archived" action="{{ route('admin.products.filter') }}" method="post">
    @csrf   
    <input type="hidden" name="archive" value="1">
    <div class="bar ">


        {{-- NOME --}}
        <div class="s-name">
            <label for="name" class="fw-semibold">Nome Prodotto</label>
            <div>
                <input type="text" class="" id="name" name="name"
                    @if (isset($filters))
                        value="{{  $filters['name'] }}"  
                    @endif > 
                
            </div>
        </div>

        {{-- VISIBILITà  --}}
        <div>
            <label for="category_id" class="form-label fw-semibold">Categoria</label>
            <select class="" id="category_id" name="category_id" >
                <option @if (!isset($filters) || $filters['category_id'] == 0) selected @endif value="0">Tutti</option>
                @foreach ($categories as $item)
                    <option @if (isset($filters) && $filters['category_id'] == $item->id) selected @endif value="{{$item->id}}"> @if($item->id == 1) non categorizzati @else {{$item->name}} @endif</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="visible" class="form-label fw-semibold">Visibilità</label>
            <select class="" id="visible" name="visible" >
                <option @if (isset($filters) && $filters['visible'] == '0') selected @endif value="0">Tutti</option>
                <option @if (isset($filters) && $filters['visible'] == '1') selected @endif value="1">Visibili</option>
                <option @if (isset($filters) && $filters['visible'] == '2') selected @endif value="2">Non visibili</option>
            </select>
        </div>
        <div>
            <label for="order" class="form-label fw-semibold">Ordina per</label>
            <select class="" id="order" name="order" >
                <option @if (isset($filters) && $filters['order'] == '0') selected @endif value="0">Ultima modifica</option>
                <option @if (isset($filters) && $filters['order'] == '1') selected @endif value="1">Nome A-Z</option>
            </select>
        </div>
       
        <div class="buttons">
         <button type="submit" class=" my_btn_3">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel-fill" viewBox="0 0 16 16">
                 <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z"/>
             </svg>  Applica
         </button>
         <a class="my_btn_1 search" href="{{ route('admin.products.archived')}}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                 <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
             </svg> Rimuovi
         </a>   
        </div>
    </div>
    
</form> 


<div class="object-container archived">
    @foreach ($products as $item)
        <div class="obj  @if (!$item->visible) not_v @endif" onclick="window.location.href='{{ route('admin.products.show', $item->id) }}">
            <h3><a href="{{ route('admin.products.show', $item) }}">{{$item->name}}</a></h3>     
            <div class="card_">
                @if (isset($item->image))
                    <img src="{{ asset('public/storage/' . $item->image) }}" alt="{{$item->name}}">
                @else
                    <img src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$item->name }}">
                @endif 

                <div class="info">
                    <section>
                        <h4>Ingredienti:</h4>
                        <p>
                            @foreach ($item->ingredients as $ingredient)     
                                {{ $ingredient->name }}{{ !$loop->last ? ', ' : '.' }}
                            @endforeach
                        </p>
                    </section>
                    <section>
                        <h4>Descrizione:</h4>
                        @if ($item->description)
                            <p>{{$item->description}}</p>
                        @else
                            <p>(nessuna)</p>
                        @endif
                    </section>
                    <div class="split_i">
                        <h4>{{$item->category->name}}</h4>
                        <div class="price">€{{$item->price / 100}}</div>
                    </div>
                </div>
            </div>
            <div class="allergens">
                @php $all = json_decode($item->allergens) @endphp
                @foreach ($all as $i)
                    <img src="{{config('configurazione.allergens')[$i]['img']}}" alt="" title="{{config('configurazione.allergens')[$i]['name']}}">
                @endforeach
            </div>
            <div class="actions">
                <a class="my_btn_1 m" href="{{ route('admin.products.edit', $item) }}">Modifica</a>
                <form action="{{ route('admin.products.status') }}" method="POST">
                    @csrf
                    <input type="hidden" name="archive" value="1">
                    <input type="hidden" name="v" value="0">
                    <input type="hidden" name="a" value="1">
                    <input type="hidden" name="id" value="{{$item->id}}">
                    <button class="my_btn_1 d" type="submit">Ripristina</button>
                </form>
                <form action="{{ route('admin.products.status') }}" method="POST">
                    @csrf
                    <input type="hidden" name="archive" value="1">
                    <input type="hidden" name="v" value="1">
                    <input type="hidden" name="a" value="0">
                    <input type="hidden" name="id" value="{{$item->id}}">
                    @if (!$item->visible)
                        <button class="my_btn_1 v op" type="submit">
                            <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
                                <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z"/>
                                <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z"/>
                            </svg>  
                        </button>
                    @else
                        <button class="my_btn_1 v" type="submit">
                            <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/>
                            </svg>    
                        </button>
                    @endif
                    
                </form>
            </div>

        </div>
    @endforeach
</div>
@endsection