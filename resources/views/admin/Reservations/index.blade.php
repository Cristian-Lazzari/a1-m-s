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
        $reservations = $data[1];
    @endphp
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        
      Filtri aggiornati
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
 
<h1>Prenotazioni Tavoli</h1>

<form class="top-bar-product" action="{{ route('admin.reservations.filter') }}" method="post">
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
         <a class="my_btn_1 search" href="{{ route('admin.reservations.index')}}">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                 <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
             </svg>  Rimuovi
         </a>   
        </div>
    </div>
    
</form> 



    {{-- Legenda  --}}


        
@foreach ($reservations as $reservation)
    @php
     $parts = explode(" ", $reservation->date_slot);
     $date = $parts[0];
     $time = $parts[1];
    @endphp 
    <div class="
    @if ($reservation->status == 2)
    my_2
    @elseif ($reservation->status == 1)
    my_1
    @elseif ($reservation->status == 0)
    my_0
    @elseif ($reservation->status == 3)
    my_3
    @elseif ($reservation->status == 5)
    my_5
    @endif
    or-res my-4"
    >
        <section class="top">
            <div class="name">
                <h4>{{$date}}</h4>
                @if (config('configurazione.double_t') && $reservation->sala !== 0)
                    <h3><strong>{{$reservation->sala == 1 ? config('configurazione.set_time_dt')[0] : config('configurazione.set_time_dt')[1]}}</strong></h3>
                @endif
                <h4>{{$reservation->surname}} {{$reservation->name}} <strong>#r-{{$reservation->id}}</strong></h4>
            </div>
            <div class="actions">
                <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="my_btn_5">Dettagli</a>
                <div class="my_btn_5">Contatta</div>
            </div>
        </section>
        <section>
            <div class="name">
                <h1 class="p">{{$time}}</h1>
                @php $n_person = json_decode($reservation->n_person); @endphp
                <h4>Ospiti:
                    @if ($n_person->adult > 0)
                        {{$n_person->adult }} {{$n_person->adult > 1 ? 'adulti' : 'adulto'}}
                    @endif
                    @if ($n_person->child > 0)
                        {{$n_person->child }} {{$n_person->child > 1 ? 'bambini' : 'bambino'}}
                    @endif
                </h4>
            </div>
            <div class="actions">
                @if (!in_array($reservation->status, [0, 1, 5]))
                <div class="w-100">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#confirmModal{{$reservation->id}}" class="w-100 my_btn_6">Conferma</button>
                </div>
                @endif
                @if(!in_array($reservation->status, [0, 1]))
                <div class="w-100">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#cancelModal{{$reservation->id}}" class="w-100 my_btn_6">{{$reservation->status == 5 ? 'Rimborsa e Annulla' : 'Annulla'}}</button>                   
                </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Modale per conferma --}}
    <div class="modal fade" id="confirmModal{{$reservation->id}}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmModalLabel{{$reservation->id}}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header c-1">
                    <h1 class="modal-title fs-5" id="confirmModalLabel{{$reservation->id}}">Gestione notifica conferma</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body c-1">
                    Ordine di: {{$reservation->name}} 
                    per il: {{$reservation->date_slot}}
                    <p>Vuoi inviare un messaggio whatsapp?</p>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('admin.reservations.status') }}" method="POST">
                        @csrf
                        <input value="1" type="hidden" name="wa">
                        <input value="1" type="hidden" name="c_a">
                        <input value="{{$reservation->id}}" type="hidden" name="id">
                        <button type="submit" class="w-100 my_btn_6">Si</button>
                    </form>
                    <form action="{{ route('admin.reservations.status') }}" method="POST">
                        @csrf
                        <input value="0" type="hidden" name="wa">
                        <input value="1" type="hidden" name="c_a">
                        <input value="{{$reservation->id}}" type="hidden" name="id">
                        <button type="submit" class="w-100 my_btn_6">NO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale per annullamento --}}
    <div class="modal fade" id="cancelModal{{$reservation->id}}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cancelModalLabel{{$reservation->id}}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header c-1">
                    <h1 class="modal-title fs-5" id="cancelModalLabel{{$reservation->id}}">Gestione notifica annullamento</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body c-1">
                    Ordine di: {{$reservation->name}} 
                    per il: {{$reservation->date_slot}}
                    <p>Vuoi inviare un messaggio whatsapp?</p>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('admin.reservations.status') }}" method="POST">
                        @csrf
                        <input value="1" type="hidden" name="wa">
                        <input value="0" type="hidden" name="c_a">
                        <input value="{{$reservation->id}}" type="hidden" name="id">
                        <button type="submit" class="w-100 my_btn_6">Si</button>
                    </form>
                    <form action="{{ route('admin.reservations.status') }}" method="POST">
                        @csrf
                        <input value="0" type="hidden" name="wa">
                        <input value="0" type="hidden" name="c_a">
                        <input value="{{$reservation->id}}" type="hidden" name="id">
                        <button type="submit" class="w-100 my_btn_6">NO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

        
      

@endsection