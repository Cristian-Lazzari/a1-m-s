<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

</head>
<body style="font-family: Arial, sans-serif; background-color: #e6e6e6; color: #04001d; margin: 0; padding: 10px 0 0 0;">
    <div style="max-width: 600px; margin: 10px auto; width:85%; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
        
        <!-- Informazioni automatizzate -->
        <p style="font-size: 10px;  margin: 5px; color: #04001d80;">* questa email viene automaticamente generata dal sistema, si prega di non rispondere a questa email</p>
        <center>

            <img style="width: 80px; margin: 25px; background-color: #090333; border-radius: 26px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.272); padding: 2px; border: solid 2px #090333;" src="{{$content_mail['app_url'] . '/img/favicon.png'}}" alt="">

        </center>


        <h1 style="text-transform :uppercase; color: #04001d; font-size: 24px; margin-bottom: 12px">{{$content_mail['title']}}</h1>
        @if (isset($content_mail['subtitle']))
        <h4 style="color: #04001db9; font-size: 16px; margin-top: 0px">{{$content_mail['subtitle']}}</h4>
        @endif
        @php
            use Carbon\Carbon;

            $dateSlot = $content_mail['date_slot']; // Es: '12/09/2022' oppure '12/09/2022 22:43'

            if (strpos($dateSlot, ' ') !== false) {
                // Caso: la stringa contiene anche l'orario
                $formattedDate = Carbon::createFromFormat('d/m/Y H:i', $dateSlot)
                    ->locale('it')
                    ->translatedFormat('l j F \a\l\l\e H:i');
            } else {
                // Caso: solo la data, senza orario
                $formattedDate = Carbon::createFromFormat('d/m/Y', $dateSlot)
                    ->locale('it')
                    ->translatedFormat('l j F');
            }
        @endphp 
        <!-- Data prenotata -->
        <p style="color: #04001d; font-size: 18px; ">Data prenotata: 
            <strong style="color: #04001d; font-size: 20px; ">{{ ucfirst($formattedDate) }}</strong>
        </p>
        
        <!-- Elenco prodotti -->
        @if($content_mail['type'] == 'or')

            <div class="carrello" style="width: 100%;">
                @foreach ($content_mail['cart']['menus'] as $i)               
                    <div class="menus" style="margin: 5px 0; background-color: #0f0744; padding: 8px 10px 8px 8px; border-radius: 8px;">
                        @if (isset($i->image))
                        <div>
                            <center>
                                <img style="width: 100px; margin: 0 5px; border-radius: 8px;" src="{{ asset('public/storage/' . $i->image) }}" alt="{{$i->name}}">
                            </center>
                        </div>
                        @endif
                        <div style="margin: 0 5px; display: flex; width:100%; justify-content: space-between; flex-wrap:wrap;">
                            <span style=" color: #f4f4f4; font-size: 25px;"> ☛ {{$i->name}}</span>
                            <span style="color: #f4f4f4; font-size: 18px; font-weight: bold; margin-left: 10px;"></span>
                            @if ($i->pivot->quantity > 1)
                                <span style="color: #f4f4f4; font-size: 18px; font-weight: bold;">* {{$i->pivot->quantity}}</span>
                            @endif
                            <span style="color: #f4f4f4; font-size: 15px;  margin-left: auto;"> € {{$i->price / 100 }}</span>
                        </div>
                        @if($i->fixed_menu == '2')
                        <br>
                            <div style="margin: 5px;" class="choices">
                                <h5 style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 5px 5px 3px;">Prodotti scelti:</h5>
                                @php
                                    // 
                                    $right_c = [];
                                    $scelti = json_decode($i->pivot->choices);
                                    foreach ($scelti as $id) {
                                        foreach ($i->products as $p) {
                                            if($p->id == $id){
                                                array_push($right_c , $p);
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @foreach ($right_c as $c)
                                    <div style="margin: 2px 10px;">
                                        <div style="margin: 0 5px; display: flex; width:100%; justify-content: space-between; flex-wrap:wrap;">
                                            <span style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 2px 0;">
                                                <strong style="color: #f4f4f4; opacity: .7; font-size: 17px; font-weight:900; ">{{$c->pivot->label}}: </strong>
                                                {{$c->name}} ({{$c->category->name}})
                                            </span>
                                            @if ($c->pivot->extra_price)   
                                                <strong style="color: #f4f4f4; font-size: 13px;  margin-left: auto;">+ €{{$c->pivot->extra_price / 100}}</strong>
                                            @endif    
                                        </div>
                                    </div>

                                @endforeach
                                
                            </div>
                        @else
                            <div style="margin: 5px;" class="prod">
                                <h5 style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 5px 5px 3px;">Prodotti nel menu:</h5>
                                @foreach ($i->products as $c)
                                    <div style="margin: 2px 10px;">
                                        <span style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 2px 0;">{{$c->name}} ({{$c->category->name}})</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    {{-- <hr style="height: 1px; background-color: #04001da1; border: none; margin: 10px 0; order-radius: 20px"> --}}
                @endforeach
                @foreach ($content_mail['cart']['products'] as $i)               
                    <?php
                        $arrD= json_decode($i->pivot->remove); ?>
                    <div class="product" style="margin: 5px 0; background-color: #0f0744; padding: 8px 10px 8px 8px; border-radius: 8px;">
                        @if (isset($i->image))
                        <div>
                            <center>
                                <img style="width: 100px; margin: 0 5px; border-radius: 8px;" src="{{ asset('public/storage/' . $i->image) }}" alt="{{$i->name}}">
                            </center>
                        </div>
                        @endif
                        <div style="display: flex; width:100%; justify-content: space-between; flex-wrap:wrap;">
                            <span style="margin: 0 5px; color: #f4f4f4; font-size: 25px;"> ☛ {{$i->name}}</span>
                            <span style="color: #f4f4f4; font-size: 18px; font-weight: bold; margin-left: 10px;"></span>
                            @if ($i->pivot->quantity > 1)
                                <span style="color: #f4f4f4; font-size: 18px; font-weight: bold;">* {{$i->pivot->quantity}}</span>
                            @endif
                            <span style="color: #f4f4f4; font-size: 15px;  margin-left: auto;"> € {{$i->price / 100 }}</span>
                        </div>
                        @if (count($i->r_option) || count($i->r_add) || count($arrD))
                        <br>
                            <div style="margin: 5px;">
                                <!-- Opzioni prodotto -->
                                @if (count($i->r_option))
                                    <div style="margin: 5px;">
                                        <h5 style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 5px 5px 3px;">Opzioni:</h5>
                                        @foreach ($i->r_option as $a)
                                            <div style="margin: 0 10px; display: flex; justify-content: space-between; flex-wrap:wrap;">
                                                <span style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 2px 0;">
                                                    {{$a->name}}
                                                </span>
                                                @if ($a->price)   
                                                    <strong style="color: #f4f4f4; font-size: 13px;  margin-left: auto;">+ €{{$a->price / 100}}</strong>
                                                @endif    
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <!-- Ingredienti extra -->
                                @if (count($i->r_add))
                                    <div style="margin: 5px;">
                                        <h5 style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 5px 5px 3px;">Ingredienti extra:</h5>
                                        @foreach ($i->r_add as $a)
                                        <div style="margin: 0 10px; display: flex; justify-content: space-between; flex-wrap:wrap;">
                                            <span style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 2px 0;">
                                                {{$a->name}}
                                            </span>
                                            @if ($a->price)   
                                                <strong style="color: #f4f4f4; font-size: 13px;  margin-left: auto;">+ €{{$a->price / 100}}</strong>
                                            @endif    
                                        </div>
                                    @endforeach
                                    </div>
                                @endif
                                <!-- Ingredienti rimossi -->
                                @if (count($arrD))
                                    <div style="margin: 5px;">
                                        <h5 style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 5px 5px 3px;">Ingredienti rimossi:</h5>
                                        @foreach ($arrD as $a)
                                            <span style="color: #f4f4f4; opacity: .7; font-size: 16px;  margin: 0px 10px;">- {{$a}}</span>
                                        @endforeach       
                                    </div>
                                @endif
                   
                            </div>
                        @endif
                    </div>
                    {{-- <hr style="height: 1px; background-color: #04001da1; border: none; margin: 10px 0; order-radius: 20px"> --}}
                @endforeach
                
            
            </div>
            <!-- Indirizzo per la consegna -->
            @if (isset($content_mail['comune']))
                <h3 style="color: #04001d; font-size: 18px; margin: 15px 0 0px;">Indirizzo per la consegna:</h3>
                <p style="color: #04001d; font-size: 16px; margin: 7px 0 0px;">{{$content_mail['address']}}, {{$content_mail['address_n']}}, {{$content_mail['comune']}}</p>
                @if ($content_mail['delivery_cost'])   
                <div style="margin: 10px 0; 0 display: flex; justify-content: space-between; flex-wrap:wrap;">
                    <span style="color: #04001d; opacity: .8; font-size: 16px; font-family: monospace">
                        Costo della consegna a domicilio:
                    </span>
                        <strong style="color: #04001d; opacity: .8; font-size: 15px;  margin-left: auto; font-family: monospace">+ €{{$content_mail['delivery_cost'] / 100}}</strong>
                    </div>
                @endif    
                {{-- <p style="color: #04001d; font-size: 16px; margin: 10px 0;">*L'importo verra pagato al momento della consegna.</p> --}}
            @else
            <!-- Totale carrello -->
            <div style="color: #04001d; font-size: 22px; margin: 15px 0; display:flex;">
                <h5 style="color: #04001d; font-size: 22px;">Totale carrello: </h5>
                <h5 style="margin-left: auto; color: #04001d; font-size: 20px; font-family: monospace;">€{{$content_mail['total_price'] / 100}}</h5>
            </div>
            
                <p style="color: #04001d; font-size: 16px; margin: 10px 0;">Modalità consegna: Ritiro asporto presso {{$content_mail['app_name']}}</p>
            @endif
    
        @elseif($content_mail['type'] == 'res')

            <!-- Sala prenotata (se applicabile) -->
            @if ($content_mail['property_adv']['dt'] && $content_mail['sala'] !== 0)

                <h3 style="color: #04001d; font-size: 16px;  margin: 10px 0;">Sala prenota: <strong>{{$content_mail['sala'] == 1 ? $content_mail['property_adv']['sala_1'] : $content_mail['property_adv']['sala_2']}}</strong></h3>

            @endif

            <!-- Numero di persone -->
            @if (is_string($content_mail['n_person']))
                @php $n_person = json_decode($content_mail['n_person'], true); @endphp
                @if ($n_person['adult'])
                    <h3 style="color: #04001d; font-size: 16px;  margin: 10px 0;">Numero di adulti: {{ $n_person['adult'] }}</h3>
                @endif
                @if ($n_person['child'])
                    <h3 style="color: #04001d; font-size: 16px;  margin: 10px 0;">Numero di bambini: {{ $n_person['child'] }}</h3>
                @endif
            @endif
        @endif

        <!-- Messaggio opzionale -->
        @if($content_mail['message'] !== NULL)
            <h4 style="color: #04001d; font-size: 16px;  margin: 5px;">Messaggio:</h4>
            <span style="color: #04001d; font-size: 16px;">{{$content_mail['message']}}</span>
        @endif


        <!-- Se destinatario è admin -->
        @if($content_mail['to'] == 'admin')             
            <!-- Bottone per chiamare -->
            <a href="tel:{{$content_mail['phone']}}" style="display: block; width: 80%; text-align: center; padding: .8rem 1.6rem; background-color: #159478; font-size: 20px; font-weight:700; color: #f4f4f4; text-decoration: none; border-radius: 5px; margin: 5px auto;">Chiama {{$content_mail['name']}}</a>
            <!-- Bottone per visualizzare nella dashboard -->
            @if ($content_mail['type'] == 'or')
                <a href="{{$content_mail['app_url']}}/admin/orders/{{$content_mail['order_id']}}" style="display: block; width: 80%; text-align: center; padding: .8rem 1.6rem; background-color: #04001d; font-size: 20px; font-weight:700; color: #f4f4f4; text-decoration: none; border-radius: 5px; margin: 5px auto;">Visualizza nella Dashboard</a>
            @elseif($content_mail['type'] == 'res')
                <a href="{{$content_mail['app_url']}}/admin/reservations/{{$content_mail['res_id']}}" style="display: block; width: 80%; text-align: center; padding: .8rem 1.6rem; background-color: #04001d; font-size: 20px; font-weight:700; color: #f4f4f4; text-decoration: none; border-radius: 5px; margin: 5px auto;">Visualizza nella Dashboard</a>
            @endif
        @endif

        {{-- @if (isset($content_mail['whatsapp_message_id']) && config('configurazione.subscription') > 2 && $content_mail['to'] == 'user' && !in_array($content_mail['status'], [0,6]))
            <p style="font-size: 13px; color: #04001d; opacity: .7;" >** Per annullare l'ordine o la prenotazione in autonomia premi questo bottone </p>
            <p style="margin: 10px;">
                <a href="{{config('configurazione.APP_URL')}}/api/client_default/?whatsapp_message_id={{$content_mail['whatsapp_message_id']}}" style="background-color: #9f2323d8; color: rgb(255, 255, 255); padding: 5px 16px; text-align: center; text-decoration: none; border-radius: 8px; font-size: 14px;">Annulla</a>
            </p>
        @endif --}}


        
    </div>
    <!-- Footer -->
    <div style="margin: 50px auto 0; background-color: #04001d; color: white; padding: 10px; text-align: center; font-size: 12px;">
        @if ($content_mail['to'] == 'user' && $content_mail['status'] !== 0)
            <p style="color: #ffffff; font-size: 12px; line-height: 1.5; margin: 5px;">
                Contatta {{$content_mail['app_name']}} se desideri annullare o modificare la tua prenotazione:
            </p>
            <p style="color: #ffffff; line-height: 1.5; margin: 15px;">
                <a href="tel:{{$content_mail['admin_phone']}}" style="background-color: #ffffff; color: rgb(0, 0, 0); padding: 8px 12px; text-align: center; text-decoration: none; border-radius: 8px; font-size: 18px;">Chiama {{$content_mail['app_name']}}</a>
            </p>
        @endif
        <p style="color: #ffffff; font-size: 12px; line-height: 1.5; margin: 5px;">&copy; 2025 {{ $content_mail['app_name'] }}. Tutti i diritti riservati.</p>
        <p style="color: #ffffff; font-size: 12px; line-height: 1.5; margin: 5px;" > Powered by <a style="color: white; text-decoration: none" href="https://future-plus.it">Future +</a></p>
    </div>
    
</body>
</html>
