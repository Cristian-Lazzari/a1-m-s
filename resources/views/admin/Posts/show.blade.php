@extends('layouts.base')

@section('contents')

 
<a class="my_btn_5 ml-auto" href="{{ route('admin.posts.index') }}">Torna ai Post</a>

<h1>Dettagli del Post</h1>
<div class="show_p">
    <h2>
        @if ($post->promo)
            <svg height="24px" class="promotion_on" version="1.2" viewBox="0 0 24 24" width="24px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><g><path d="M9.362,9.158c0,0-3.16,0.35-5.268,0.584c-0.19,0.023-0.358,0.15-0.421,0.343s0,0.394,0.14,0.521    c1.566,1.429,3.919,3.569,3.919,3.569c-0.002,0-0.646,3.113-1.074,5.19c-0.036,0.188,0.032,0.387,0.196,0.506    c0.163,0.119,0.373,0.121,0.538,0.028c1.844-1.048,4.606-2.624,4.606-2.624s2.763,1.576,4.604,2.625    c0.168,0.092,0.378,0.09,0.541-0.029c0.164-0.119,0.232-0.318,0.195-0.505c-0.428-2.078-1.071-5.191-1.071-5.191    s2.353-2.14,3.919-3.566c0.14-0.131,0.202-0.332,0.14-0.524s-0.23-0.319-0.42-0.341c-2.108-0.236-5.269-0.586-5.269-0.586    s-1.31-2.898-2.183-4.83c-0.082-0.173-0.254-0.294-0.456-0.294s-0.375,0.122-0.453,0.294C10.671,6.26,9.362,9.158,9.362,9.158z"></path></g></g></svg>
        @endif
        {{$post->title}}</h2>
    <div class="split_p">
        <div class="image_p">
            @if (isset($post->image))
                <img class="logo_" src="{{ asset('public/storage/' . $post->image) }}" alt="{{$post->name }}">
            @else 
                <img class="logo_" src="https://db.kojo-sushi.it/public/images/or.png" alt="{{$post->name }}">
            @endif 
            
        </div>
        <div class="info">
            <section>
                <h4>Descrizione:</h4> 
                <p>{{$post->description}} </p>       
            </section>
            <section>
                <h4>Precedenza: <strong>{{$post->order}}</strong></h4>      
                @if (isset($post->link)) 
                    <h4 class="">Link:</h4>
                    <a class="link" href="{{$post->link}}">{{$post->link}}</a>
                @else
                    <p>(nessun link impostato)</p>   
                @endif  
            </section>

            <section class="split_i">
                
                <h4 class="">Pagina: </h4>
                <p class="">{{$post->path == '1' ? 'News' : 'Story'}}</p>
                @if (isset($post->link))
                    <h4>Hashtag:</h4>
                    <p class="">{{$post->hashtag}}</p>
                @else
                    <p class="">(nessun hashtag impostato)</p>   
                @endif 
            </section>
        </div>
    </div>
    <div class="prod-spec">
        
        <div class="actions">
            <form action="{{ route('admin.posts.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="1">
                <input type="hidden" name="a" value="0">
                <input type="hidden" name="id" value="{{$post->id}}">
                @if (!$post->visible)
                    <button class="my_btn_4" type="submit">
                       PUBBLICA
                    </button>
                @else
                    <button class="my_btn_4" type="submit">
                        Nascondi   
                    </button>
                @endif
                
            </form>
            <a class="my_btn_1" href="{{ route('admin.posts.edit', $post) }}">Modifica</a>
            <form action="{{ route('admin.posts.status') }}" method="POST">
                @csrf
                <input type="hidden" name="archive" value="0">
                <input type="hidden" name="v" value="0">
                <input type="hidden" name="a" value="1">
                <input type="hidden" name="id" value="{{$post->id}}">
                <button class="my_btn_2" type="submit">Archivia</button>
            </form>
            <form action="{{ route('admin.posts.destroy', ['post'=>$post]) }}" method="post" >
                @method('delete')
                @csrf
                <button class="my_btn_1 bg-danger" type="submit">Elimina</button>
            </form>
             
        </div>
    </div>
    <p></p>
    <p>Data creazione: {{$post->created_at}}, Ultima modifica: {{$post->updated_at}}.</p>
</div>

 

@endsection