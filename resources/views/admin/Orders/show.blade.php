@extends('layouts.base')

@section('contents')
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-light my-3">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-90deg-left" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.146 4.854a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H12.5A2.5 2.5 0 0 1 15 6.5v8a.5.5 0 0 1-1 0v-8A1.5 1.5 0 0 0 12.5 5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4z"/></svg>
</a>

    {{-- <img src="{{ Vite::asset('resources/img/picsum30.jpg') }}" alt=""> --}}
    <div class="myres-c">

        <?php

        $data_ora = DateTime::createFromFormat('d/m/Y H:i', $order->date_slot);

        $ora_formatata = $data_ora->format('H:i');
        $data_formatata = $data_ora->format('d/m/Y');
        $giorno_settimana = $data_ora->format('w');
        ?>



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
            @endif myres"
        >

            <div class="mail-tel">
                <a href="{{'mailto:' . $order->email}}" class="mail">{{$order->email}}</a>
                <a href="{{'tel:' . $order->phone}}" class="tel">{{$order->phone}}</a>
            </div>
            <div class="body">
                <section class="myres-left">
                    <h5><strong>#o-{{$order->id}}</strong></h5>
                    <div class="name">{{$order->name}}</div>
                    <div class="myres-left-c">
                        <div class="time">{{$ora_formatata}}</div>

                        <div class="day_w">{{[' ','lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'][$giorno_settimana]}}</div>
                        <div class="date">{{$data_formatata}}</div>
                    </div>
                    <div class="c_a">inviato alle: {{$order->created_at}}</div>
                    <div class="c_a">Marketing sul contatto: {{$order->news_letter ? 'si' : 'no'}}</div>
                </section>
                <section class="myres-center">
                    <h3>Prodotti</h3>
                    

                    @foreach ($orderProduct as $i)
                    
                        @if ($order->id == $i->order_id)
                            @foreach ($order->products as $o)
                            
                                @if ($o->id == $i->product_id)
                                <?php $name= $o->name ?>
                                @endif
                                
                            @endforeach
                            <?php
                                $arrO= json_decode($i->option); 
                                $arrA= json_decode($i->add); 
                                $arrD= json_decode($i->remove); 
                            ?>
                            <div class="product">
                                <div class="counter">* {{$i->quantity}}</div>              
                                <div class="name">{{$name}}</div>
                                <div class="variations">
                                    @if ($arrO !== [])
                                    <div class="options">
                                        <h5>Opzioni:</h5>
                                        @foreach ($arrO as $a)
                                        <span>+ {{$a}}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div class="bottom-var">
                                        @if ($arrA !== [])
                                        <div class="add">
                                            <h5>Ingredienti extra:</h5>
                                            @foreach ($arrA as $a)
                                            <span>+ {{$a}}</span>
                                            @endforeach
                                        </div>
                                        @endif
                                        @if ($arrD !== [])
                                        <div class="removed">
                                            <h5>Ingredienti rimossi:</h5>
                                            @foreach ($arrD as $a)
                                            <span>- {{$a}}</span>
                                            @endforeach       
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                            </div>
                        @endif
                    @endforeach
                    
                    <div class="price">Costo di consegna €{{$delivery_cost / 100}}</div>
                    <div class="t_price">€{{$order->tot_price / 100}}</div>
                    {{-- <div class="t_price">{{$order->total_pz_q}} pezzi taglio</div>
                    <div class="t_price">{{$order->total_pz_t}} pizze piatte</div>
                     --}}
                </section>
                <section class="myres-right">
                    @if (in_array($order->status, [2, 3]))
                    <div class="w-100">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#confirmModal" class="w-100 my_btn_6">Conferma</button>
                    </div>
                    @endif
                    @if(in_array($order->status, [2, 3, 5]))
                    <div class="w-100">
                        <button type="button" data-bs-toggle="modal" data-bs-target="#cancelModal" class="w-100 my_btn_6">{{in_array($order->status, [3, 5]) ? 'Rimborsa e Annulla' : 'Annulla'}}</button>                   
                    </div>
                    @endif
                    
                    @if (isset($order->comune))
                        <h3>
                            Consegnare a domicilio
                            <p>{{$order->comune}}, {{$order->address}}, {{$order->address_n}}</p>
                        </h3>
                    @else
                        <h3>
                            Ritiro d'asporto
                        </h3>
                    @endif
                </section>
            </div>
            {{-- <div class="visible">
                @if ($order->status == 2)
                <span>in elaborazione</span>
                @elseif ($order->status == 1)
                <span>confermato</span>
                @elseif ($order->status == 0)
                <span>annullato</span>
                @endif
            </div> --}}
        </div>

        
    </div>

<!-- Modale per la conferma -->
<div class="modal fade" id="confirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header c-1">
                <h1 class="modal-title fs-5" id="confirmModalLabel">Gestione notifica per conferma</h1>
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
<div class="modal fade" id="cancelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header c-1">
                <h1 class="modal-title fs-5" id="cancelModalLabel">Gestione notifica per annullamento</h1>
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

@endsection