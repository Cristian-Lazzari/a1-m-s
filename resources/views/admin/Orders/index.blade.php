@extends('layouts.base')



@section('contents')
@if (session('success'))
    @php
        $data = session('success')
    @endphp
    <div class="alert alert-primary">
        {{ $data }}
    </div>
@endif
@if (session('filter'))
    @php
        $data = session('filter');
        $filters = $data[0];
        $orders = $data[1];
    @endphp
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        
      Filtri aggiornati
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
<h1>Ordinazioni d'asporto / domicilio</h1>

<form class="top-bar-product" action="{{ route('admin.orders.filter') }}" method="post">
    @csrf   
    <div class="bar">
        <div class="s-name">
            <label for="name" class="fw-semibold">Nome Cliente</label>
            <div>
                <input type="text" class="" id="name" name="name"
                    @if (isset($filters))
                        value="{{  $filters['name'] }}"  
                    @endif > 
                
            </div>
        </div>
        <div class="s-name">
            <label for="date" class="fw-semibold">Data</label>
            <div>
                <input type="date" class="" id="date" name="date"
                    @if (isset($filters))
                        value="{{  $filters['date'] }}"  
                    @endif > 
                
            </div>
        </div>


        <div>
            <label for="status" class="form-label fw-semibold">Status</label>
            <select class="" id="status" name="status" >
                <option selected disabled value="3">seleziona uno status</option>
                <option @if (isset($filters) && $filters['status'] == '1') selected @endif value="1">Confermate</option>
                <option @if (isset($filters) && $filters['status'] == '2') selected @endif value="2">In Elaborazione</option>
                <option @if (isset($filters) && $filters['status'] == '5') selected @endif value="5">Pagate</option>
                <option @if (isset($filters) && $filters['status'] == '4') selected @endif value="4">Annullate</option>
                <option @if (isset($filters) && $filters['status'] == '3') selected @endif value="3">Tutte</option>
            </select>
        </div>
        <div>
            <label for="order" class="form-label fw-semibold">Ordina</label>
            <select class="" id="order" name="order" >
                <option selected disabled value="1">seleziona uno ordine</option>
                <option @if (isset($filters) && $filters['order'] == '0') selected @endif value="0">Data di creazione</option>
                <option @if (isset($filters) && $filters['order'] == '1') selected @endif value="1">Data di prenotazione</option>
            </select>
        </div>
        
       
        <div class="buttons">
         <button type="submit" class=" my_btn_3">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel-fill" viewBox="0 0 16 16">
                <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z"/>
             </svg>  Applica
         </button>
         <a class="my_btn_1 search" href="{{ route('admin.orders.index')}}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                 <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
             </svg>  Rimuovi
         </a>   
        </div>
    </div>
    
</form> 
         
@foreach ($orders as $order)

@php
    $parts = explode(" ", $order->date_slot);
    $date = $parts[0];
    $time = $parts[1];
@endphp 
    <div class="
    @if ($order->status == 2)
    my_2
    @elseif ($order->status == 1)
    my_1
    @elseif ($order->status == 0)
    my_0
    @elseif ($order->status == 3)
    my_3
    @elseif ($order->status == 5)
    my_5
    @elseif ($order->status == 6)
    my_6
    @endif
    or-res my-4"
    >
    <section class="top">
        <div class="name">
            <h4>{{$date}} <strong>#o-{{$order->id}}</strong></h4>
            <h3>{{$order->surname}} {{$order->name}}</h3>
        </div>
        
        <div class="actions">
            <a href="{{ route('admin.orders.show', $order->id) }}" class="my_btn_5">Dettagli</a>
            <div class="my_btn_5">Contatta</div>
        </div>
    </section>
    <section>
        <div class="name">
            <h1 class="p">{{$time}}
            @if (isset($order->comune))
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mailbox-flag" viewBox="0 0 16 16">
                    <path d="M10.5 8.5V3.707l.854-.853A.5.5 0 0 0 11.5 2.5v-2A.5.5 0 0 0 11 0H9.5a.5.5 0 0 0-.5.5v8zM5 7c0 .334-.164.264-.415.157C4.42 7.087 4.218 7 4 7s-.42.086-.585.157C3.164 7.264 3 7.334 3 7a1 1 0 0 1 2 0"/>
                    <path d="M4 3h4v1H6.646A4 4 0 0 1 8 7v6h7V7a3 3 0 0 0-3-3V3a4 4 0 0 1 4 4v6a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V7a4 4 0 0 1 4-4m0 1a3 3 0 0 0-3 3v6h6V7a3 3 0 0 0-3-3"/>
                </svg>
            @endif
            </h1>
            <h4>Totale ordine: € {{$order->tot_price / 100}}</h4>
        </div>
        <div class="actions">
            @if (in_array($order->status, [2, 3]))
            <div class="w-100">
                <button type="button" data-bs-toggle="modal" data-bs-target="#confirmModal{{$order->id}}" class="w-100 my_btn_6">Conferma</button>
            </div>
            @endif
            @if(in_array($order->status, [2, 3, 5]))
            <div class="w-100">
                <button type="button" data-bs-toggle="modal" data-bs-target="#cancelModal{{$order->id}}" class="w-100 my_btn_6">{{in_array($order->status, [3, 5]) ? 'Rimborsa e Annulla' : 'Annulla'}}</button>                   
            </div>
            @endif
        </div>
    </section>
</div>

<!-- Modale per la conferma -->
<div class="modal fade" id="confirmModal{{$order->id}}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmModalLabel{{$order->id}}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header c-1">
                <h1 class="modal-title fs-5" id="confirmModalLabel{{$order->id}}">Gestione notifica per conferma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body c-1">
                Ordine di: {{$order->name}} 
                per il: {{$order->date_slot}}
                <p>Vuoi inviare un messaggio whatsapp?</p>
            </div>
            <div class="modal-footer">
                <form action="{{ route('admin.orders.status') }}" method="POST">
                    @csrf
                    <input value="1" type="hidden" name="wa">
                    <input value="1" type="hidden" name="c_a">
                    <input value="{{$order->id}}" type="hidden" name="id">
                    <button type="submit" class="w-100 my_btn_6">Si</button>
                </form>
                <form action="{{ route('admin.orders.status') }}" method="POST">
                    @csrf
                    <input value="0" type="hidden" name="wa">
                    <input value="1" type="hidden" name="c_a">
                    <input value="{{$order->id}}" type="hidden" name="id">
                    <button type="submit" class="w-100 my_btn_6">NO</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modale per l'annullamento -->
<div class="modal fade" id="cancelModal{{$order->id}}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cancelModalLabel{{$order->id}}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header c-1">
                <h1 class="modal-title fs-5" id="cancelModalLabel{{$order->id}}">Gestione notifica per annullamento</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body c-1">
                Ordine di: {{$order->name}} 
                per il: {{$order->date_slot}}
                <p>Vuoi inviare un messaggio whatsapp?</p>
            </div>
            <div class="modal-footer">
                <form action="{{ route('admin.orders.status') }}" method="POST">
                    @csrf
                    <input value="1" type="hidden" name="wa">
                    <input value="0" type="hidden" name="c_a">
                    <input value="{{$order->id}}" type="hidden" name="id">
                    <button type="submit" class="w-100 my_btn_6">Si</button>
                </form>
                <form action="{{ route('admin.orders.status') }}" method="POST">
                    @csrf
                    <input value="0" type="hidden" name="wa">
                    <input value="0" type="hidden" name="c_a">
                    <input value="{{$order->id}}" type="hidden" name="id">
                    <button type="submit" class="w-100 my_btn_6">NO</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endforeach

      

@endsection