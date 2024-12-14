@extends('layouts.base')



@section('contents')


@if (session('not_found'))
    @php
        $data = session('not_found')
    @endphp
    <div class="alert alert-danger">
        La data non ha orari a cui è possibile ordinare o prenotare
    </div>
@endif
@if (session('success'))
    @php
        $data = session('success')
    @endphp
    <div class="alert alert-success">
        {{ $data }}
    </div>
@endif
 

@if (isset($year))
<div class="date_index">
    <div id="carouselExampleIndicators" class="carousel slide my_carousel">
        <div class="carousel-indicators">

            @php 
                $i = 0; 
                $currentDay = date("d");
                $currentMonth = date("m");
                $currentYear = date("Y");
            @endphp
            @foreach ($year as $m)
                <button  type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="{{$i}}"
                @if ($currentMonth == $m['month'] && $currentYear == $m['year'])
                    class="active" aria-current="true" 
                @endif
                aria-label="{{ 'Slide ' . $i }}"></button>
                @php $i ++ @endphp
            @endforeach
        </div>
        <div class="carousel-inner">
        @php $i = 0; @endphp
        @foreach ($year as $m)
            <div class="carousel-item
            @if ($currentMonth == $m['month'] && $currentYear == $m['year'])
                active 
            @endif
            ">
                
                <h2 class="my">{{['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'][$m['month']]}} - {{$m['year']}}</h2>
                <div class="calendar-c">
                    
                    <div class="c-name">
                        @php
                        $day_name = ['lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'];
                        @endphp
                        @foreach ($day_name as $item)
                            <h4>{{$item}}</h4>
                        @endforeach
                    </div>
                    <div class="calendar">

                        @foreach ($m['days'] as $d)
                        
                            <form action="{{ route('admin.dates.showDay') }}" class="day {{ 'd' . $d['day_w']}} @if(!isset($d['time'])) day-off @endif @if($currentMonth == $m['month'] && $currentYear == $m['year'] && $currentDay == $d['day']) day-active @endif " style="grid-column-start:{{$d['day_w'] }}" method="get">
                                @csrf
                                <input type="hidden" name="date" value="{{$d['date']}}">
                                @if(isset($d['asporto']) && $d['asporto'] !== 0)
                                    <p class="pop1">
                                        <span>{{$d['asporto']}}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-inboxes" viewBox="0 0 16 16">
                                            <path d="M4.98 1a.5.5 0 0 0-.39.188L1.54 5H6a.5.5 0 0 1 .5.5 1.5 1.5 0 0 0 3 0A.5.5 0 0 1 10 5h4.46l-3.05-3.812A.5.5 0 0 0 11.02 1zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562A.5.5 0 0 0 1.884 9h12.234a.5.5 0 0 0 .496-.438zM3.809.563A1.5 1.5 0 0 1 4.981 0h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 10H1.883A1.5 1.5 0 0 1 .394 8.686l-.39-3.124a.5.5 0 0 1 .106-.374zM.125 11.17A.5.5 0 0 1 .5 11H6a.5.5 0 0 1 .5.5 1.5 1.5 0 0 0 3 0 .5.5 0 0 1 .5-.5h5.5a.5.5 0 0 1 .496.562l-.39 3.124A1.5 1.5 0 0 1 14.117 16H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .121-.393zm.941.83.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438l.32-2.562H10.45a2.5 2.5 0 0 1-4.9 0z"/>
                                        </svg> 
                                    </p>
                                @endif
                                @if(isset($d['domicilio'])  && $d['domicilio'] !== 0)
                                <p class="pop2">
                                    <span>{{$d['domicilio']}}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mailbox-flag" viewBox="0 0 16 16">
                                            <path d="M10.5 8.5V3.707l.854-.853A.5.5 0 0 0 11.5 2.5v-2A.5.5 0 0 0 11 0H9.5a.5.5 0 0 0-.5.5v8zM5 7c0 .334-.164.264-.415.157C4.42 7.087 4.218 7 4 7s-.42.086-.585.157C3.164 7.264 3 7.334 3 7a1 1 0 0 1 2 0"/>
                                            <path d="M4 3h4v1H6.646A4 4 0 0 1 8 7v6h7V7a3 3 0 0 0-3-3V3a4 4 0 0 1 4 4v6a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V7a4 4 0 0 1 4-4m0 1a3 3 0 0 0-3 3v6h6V7a3 3 0 0 0-3-3"/>
                                        </svg> 
                                    </p>
                                @endif
                                @if(isset($d['table']) && $d['table'] !== 0)
                                    <p class="pop3">
                                        <span>{{$d['table']}}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                            <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1z"/>
                                        </svg>
                                    </p>
                                @endif
                                <button class="b">{{$d['day']}}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
            @php $i ++ @endphp
        @endforeach

        </div>
        <button class="carousel-control-prev" style="width: 7% !important;" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
            <div class="lez-c prev">
                <div class="line"></div>
                <div class="line l2"></div>
            </div>
        </button>
        <button class="carousel-control-next" style="width: 7% !important;" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
            <div class="lez-c ">
                <div class="line"></div>
                <div class="line l2"></div>
            </div>
        </button>
    </div>
</div>
@else 
<div class="date-off">

<p >Non sono ancora state impostate le disponibilita dei servizi, clicca QUI e impostale ora</p>
</div>
@endif
<div class="container ">
    <form class="d-flex flex-column py-5"  action="{{ route('admin.dates.generate') }}" method="post" enctype="multipart/form-data">
    <h1>Genera nuove disponibilità</h1>
        @csrf
        
        @if ( config('configurazione.pack') == 2 ||  config('configurazione.pack') == 4)  
            @if (config('configurazione.double_t'))  
                <h5 class="pt-4 ">Indica il numero di posti a sedere per la sala sushi per fascia oraria</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_reservations_1" class="input-group-text" >N° di posti a sedere Sushi</label>
                    <input name="max_reservations_1" id="max_reservations_1" type="number" class="form-control" placeholder="N° di posti a sedere" aria-label="N° di posti a sedere" aria-describedby="addon-wrapping" >
                </div> @error('max_reservations_1') <p class="error">{{ $message }}</p> @enderror
                <h5 class="pt-4 ">Indica il numero di posti a sedere per la sala ITA per fascia oraria</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_reservations_2" class="input-group-text" >N° di posti a sedere ITA</label>
                    <input name="max_reservations_2" id="max_reservations_2" type="number" class="form-control" placeholder="N° di posti a sedere" aria-label="N° di posti a sedere" aria-describedby="addon-wrapping" >
                </div> @error('max_reservations_2') <p class="error">{{ $message }}</p> @enderror
            @else
                <h5 class="pt-4 ">Indica il numero di posti a sedere per fascia oraria</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_reservations" class="input-group-text" >N° di posti a sedere</label>
                    <input name="max_reservations" id="max_reservations" type="number" class="form-control" placeholder="N° di posti a sedere" aria-label="N° di posti a sedere" aria-describedby="addon-wrapping" >
                </div> @error('max_reservations') <p class="error">{{ $message }}</p> @enderror
            @endif
        @endif
        @if ( config('configurazione.pack') == 3 ||  config('configurazione.pack') == 4)  
            @if (config('configurazione.typeOfOrdering'))  
                <h5 class="pt-4 ">Indica il numero massimo di {{config('configurazione.set_time')[1]}} (cucina 1) per asporto/delivery</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_cucina_1" class="input-group-text" >N° di pezzi</label>
                    <input name="max_cucina_1" id="max_cucina_1" type="number" class="form-control" placeholder="N° di pezzi">
                </div> @error('max_cucina_1') <p class="error">{{ $message }}</p> @enderror
                
                <h5 class="pt-4 ">Indica il numero massimo di {{config('configurazione.set_time')[2]}} (cucina 2) per asporto/delivery</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_cucina_2" class="input-group-text" >N° di pizze</label>
                    <input name="max_cucina_2" id="max_cucina_2" type="number" class="form-control" placeholder="N° di pezzi">
                </div> @error('max_cucina_2') <p class="error">{{ $message }}</p> @enderror
            @else
                <h5 class="pt-4 ">Indica il numero massimo di ordini per l'asporto</h5>
                <div class="input-group w-auto flex-nowrap py-2 ">
                    <label for="max_asporto" class="input-group-text" >N° di ordini</label>
                    <input name="max_asporto" id="max_asporto" type="number" class="form-control" placeholder="N° di ordini per fascia oraria">
                </div> @error('max_asporto') <p class="error">{{ $message }}</p> @enderror
                    
            @endif
            <h5 class="pt-4 ">Indica il numero massimo di ordini con la consegna a domicilio</h5>
            <div class="input-group w-auto flex-nowrap py-2 ">
                <label for="max_domicilio" class="input-group-text" >N° di oridini a domicilio</label>
                <input name="max_domicilio" id="max_domicilio" type="number" class="form-control" placeholder="N° di ordini per fascia oraria">
            </div> @error('max_domicilio') <p class="error">{{ $message }}</p> @enderror
        @endif
        <div>
            <h5 class="pt-4">Seleziona i giorni in cui sei attivo</h5>
            <div class="day_form" role="group" aria-label="Basic checkbox toggle button group">

                @foreach ([1, 2, 3, 4, 5, 6, 7] as $day)
                
                    <input class="btn-check"  name="day[]" data-bs-toggle="collapse" data-bs-target="#multiCollapseExample{{$day}}" aria-expanded="false" aria-controls="multiCollapseExample{{$day}}" id="day_{{ $day }}" value="{{ $day }}">
                    <label class="my_btn_1 my_btn_2 scale-none  " for="day_{{ $day }}">{{ [' ','lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'][$day] }}
                        <div class="collapse multi-collapse" id="multiCollapseExample{{$day}}">
                            <div class="card card-body">
                                {{-- <input
                                    type="checkbox"
                                    class="btn-check"
                                    id="days_on_{{ $day }}"
                                    name="days_on[]"
                                    value="{{ $day }}">
                                <label class="btn btn-outline-dark w-auto m-auto" for="days_on_{{ $day }}">Attiva</label> --}}

                                <label class="theme-switch" for="days_on_{{ $day }}" id="themeswitch">
                                    <input type="checkbox" id="days_on_{{ $day }}" name="days_on[]" value="{{ $day }}">
                                    <div class="slider round"></div>
                                    <span class="name"></span>
                                    <div class="back"></div>
                                </label>
                                <h5 class="p-3">Seleziona le fasce orarie disponibili</h5>
                                @php
                                    $day_time = [];
                                    $start = new DateTime(config('configurazione.times_start'));
                                    $end = new DateTime(config('configurazione.times_end'));
                                    $index = 1;
                                    $interval = config('configurazione.times_interval');

                                    // Loop finché l'orario di inizio è inferiore all'orario di fine
                                    while ($start <= $end) {
                                        $day_time[$index] = [
                                            'time' => $start->format('H:i'),
                                            'set' => ''
                                        ];
                                        // Incrementa l'orario di inizio con l'intervallo specificato
                                        $start->modify("+$interval minutes");
                                        $index++;
                                    }
                                @endphp

                                @foreach ($day_time as $time)
                                    <select  class="form-select col" name="times_slot_{{$day}}[]" id="">
                                        @if ( config('configurazione.pack') == 2)
                                            <option value="0">{{ $time['time'] }} - ND</option>
                                            <option value="1">{{ $time['time'] }} - attivo</option>  
                                        @elseif ( config('configurazione.pack') == 3)  
                                            <option value="0">{{ $time['time'] }} - ND</option>
                                            <option value="1">{{ $time['time'] }} - asporto</option>
                                            <option value="4">{{ $time['time'] }} - domicilio</option>
                                            <option value="7">{{ $time['time'] }} - tutti</option>
                                        @elseif ( config('configurazione.pack') == 4)     
                                            <option value="0">{{ $time['time'] }} - ND</option>
                                            <option value="1">{{ $time['time'] }} - asporto</option>
                                            <option value="2">{{ $time['time'] }} - tavoli</option>
                                            <option value="3">{{ $time['time'] }} - asporto/tavoli</option>
                                            <option value="4">{{ $time['time'] }} - domicilio</option>
                                            <option value="5">{{ $time['time'] }} - domicilio/asporto</option>
                                            <option value="6">{{ $time['time'] }} - domicilio/tavoli</option>
                                            <option value="7">{{ $time['time'] }} - tutti</option>
                                        @endif
                                    </select>
                                @endforeach                    
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
        @error('days_on') <p class="error m-2">seleziona "Attiva" nei giorni i cui sei operativo</p> @enderror
        <button class="btn btn-outline-light mt-4 w-100">Modifica</button>
    </form>
</div>


@endsection