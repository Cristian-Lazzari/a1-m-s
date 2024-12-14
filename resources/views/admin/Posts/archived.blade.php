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
 
<h1>Post - archivio</h1>
 
<form class="top-bar-product archived" action="{{ route('admin.posts.filter') }}" method="post">
    @csrf   
    <input type="hidden" name="archive" value="1">
    
    <div class="bar">
        {{-- NOME --}}
        <div class="s-name">
            <label for="name" class="fw-semibold">Nome post</label>
            <div>
                <input type="text" class="" id="title" name="title"
                    @if (isset($filters))
                        value="{{  $filters['title'] }}"  
                    @endif > 
                
            </div>
        </div>    
        {{-- VISIBILITà  --}}
        <div>
            <label for="visible" class="form-label fw-semibold">Visibilità</label>
            <select class="" id="visible" name="visible" >
                <option @if (isset($filters) && $filters['visible'] == '0') selected @endif value="0">Tutti</option>
                <option @if (isset($filters) && $filters['visible'] == '1') selected @endif value="1">Visibili</option>
                <option @if (isset($filters) && $filters['visible'] == '2') selected @endif value="2">Non visibili</option>
            </select>
        </div>
        <div>
            <label for="path" class="form-label fw-semibold">Path</label>
            <select class="" id="path" name="path" >
                <option @if (isset($filters) && $filters['path'] == '0') selected @endif value="0">Tutti</option>
                <option @if (isset($filters) && $filters['path'] == '1') selected @endif value="1">News</option>
                <option @if (isset($filters) && $filters['path'] == '2') selected @endif value="2">Storia</option>
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
         <a class="my_btn_1 search" href="{{ route('admin.posts.index')}}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                 <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
             </svg> Rimuovi
         </a>   
        </div>
    </div>
    
</form> 


<div class="object-container archived">
    @foreach ($posts as $item)

    <div class="post  @if (!$item->visible) not_v @endif" onclick="window.location.href='{{ route('admin.posts.show', $item->id) }}">
        <h3>
            @if ($item->promo)
                <svg height="24px" class="promotion_on" version="1.2" viewBox="0 0 24 24" width="24px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><g><path d="M9.362,9.158c0,0-3.16,0.35-5.268,0.584c-0.19,0.023-0.358,0.15-0.421,0.343s0,0.394,0.14,0.521    c1.566,1.429,3.919,3.569,3.919,3.569c-0.002,0-0.646,3.113-1.074,5.19c-0.036,0.188,0.032,0.387,0.196,0.506    c0.163,0.119,0.373,0.121,0.538,0.028c1.844-1.048,4.606-2.624,4.606-2.624s2.763,1.576,4.604,2.625    c0.168,0.092,0.378,0.09,0.541-0.029c0.164-0.119,0.232-0.318,0.195-0.505c-0.428-2.078-1.071-5.191-1.071-5.191    s2.353-2.14,3.919-3.566c0.14-0.131,0.202-0.332,0.14-0.524s-0.23-0.319-0.42-0.341c-2.108-0.236-5.269-0.586-5.269-0.586    s-1.31-2.898-2.183-4.83c-0.082-0.173-0.254-0.294-0.456-0.294s-0.375,0.122-0.453,0.294C10.671,6.26,9.362,9.158,9.362,9.158z"></path></g></g></svg>
            @endif
            <a href="{{ route('admin.posts.show', $item) }}">{{$item->title}}</a>
        </h3>     
        <div class="card_">
            @if (isset($item->image))
                <img src="{{ asset('public/storage/' . $item->image) }}" alt="{{$item->title}}">
            @else
                <img src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$item->title }}">
            @endif 

            @if (isset($item->hashtag))
                <div class="price">{{$item->hashtag}}</div>
            @endif 
            <h4 class="ell-c">Pagina: <span class="">{{$item->path == '1' ? 'News' : 'Story'}}</span></h4>
            <div class="info">
                <section>
                    <h4>Precedenza: <strong>{{$item->order}}</strong></h4>      
                    @if (isset($item->link)) 
                        <h4 class="ell-c">Link: <a href="{{$item->link}}" class="ellips">{{$item->link}}</a></h4>
                    @endif  
                </section>
            </div>
        </div>
        <div class="actions">
            <a class="my_btn_1 m" href="{{ route('admin.posts.edit', $item) }}">Modifica</a>
            <form action="{{ route('admin.posts.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="0">
                <input type="hidden" name="a" value="1">
                <input type="hidden" name="id" value="{{$item->id}}">
                <button class="my_btn_1 d" type="submit">Ripristina</button>
            </form>
            <form action="{{ route('admin.posts.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="1">
                <input type="hidden" name="a" value="0">
                <input type="hidden" name="id" value="{{$item->id}}">
                @if (!$item->visible)
                    <button class="my_btn_5" type="submit">
                        PUBBLICA
                    </button>
                @else
                    <button class="my_btn_5" type="submit">
                        Nascondi   
                    </button>
                @endif
                
            </form>
        </div>

    </div>
    @endforeach
</div>
@endsection