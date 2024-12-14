@extends('layouts.base')



@section('contents')
@php
      //se impostato a true gli ordini vengono presi in base ai pezzi altrimenti in base al numero di ordini
    $domain = 'https://future-plus.it/allergens/';
@endphp
@if (session('ingredient_success'))
    @php
        $data = session('ingredient_success')
    @endphp
    <div class="alert alert-primary">
        {{ $data }}
    </div>
@endif
@if (session('delete_success'))
    @php
        $data = session('delete_success')
    @endphp
    <div class="alert alert-danger">
        {{ $data }}
    </div>
@endif

 

<h1>Ingredienti e opzioni extra per prodotti</h1>

<div class="action-page">
    <a class="my_btn_2 m-1 w-auto" href="{{ route('admin.ingredients.create') }}">Crea un nuovo ingrediente</a>
</div>
<h2 class="my-4">Opzioni extra per prodotti:</h2>
<div class="slim_cont">
    @foreach ($options as $item)

        <div class="slim_ ">
            <section class="s1">

                @if (isset($item->icon))
                    <img src="{{ asset('public/storage/' . $item->icon) }}" alt="{{$item->name }}">
                @else 
                    <img src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$item->name }}">
                @endif 
    
                <h3><a href="{{ route('admin.ingredients.show', $item) }}">{{$item->name}}</a></h3>     
            </section>
            <section>
                <div class="price">€{{$item->price / 100}}</div>         
                <div class="actions">
                    <a class="" href="{{ route('admin.ingredients.edit', $item) }}">
                        <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                        </svg>
                    </a>
                    <form action="{{ route('admin.ingredients.destroy', ['ingredient'=>$item]) }}" method="post" >
                        @method('delete')
                        @csrf
                        <button class="s_d" type="submit">
                            <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                            </svg>
                        </button>
                    </form>
                    
                </div>
            </section>

        </div>
    @endforeach
</div>
<h2 class="my-4 mt-5">Ingredienti:</h2>
<div class="slim_cont">
    @foreach ($ingredients as $item)

        <div class="slim_ ">
            <section class="s1">

                @if (isset($item->icon))
                    <img src="{{ asset('public/storage/' . $item->icon) }}" alt="{{$item->name}}">
                @else
                    <img src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$item->name }}">
                @endif 
    
                <h3><a href="{{ route('admin.ingredients.show', $item) }}">{{$item->name}}</a></h3>     
            </section>
            <section>
                <div class="price">€{{$item->price / 100}}</div>         
                <div class="actions">
                    <a class="" href="{{ route('admin.ingredients.edit', $item) }}">
                        <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                        </svg>
                    </a>
                    <form action="{{ route('admin.ingredients.destroy', ['ingredient'=>$item]) }}" method="post" >
                        @method('delete')
                        @csrf
                        <button class="s_d" type="submit">
                            <svg style="vertical-align: sub" xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                            </svg>
                        </button>
                    </form>
                    
                </div>
            </section>

        </div>
    @endforeach
</div>
@endsection