<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Conferma Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 10px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
        
        <!-- Informazioni automatizzate -->
        <p style="font-size: 16px; line-height: 1.8; margin: 5px;">* questa email viene automaticamente generata dal sistema, si prega di non rispondere a questa email</p>
        
        @if ($content_mail['type'] == 'or')
            <!-- Messaggi per tipo 'or' -->
            @if ($content_mail['to'] == 'admin')
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">{{ $content_mail['name'] }}{{$content_mail['status'] == 3 ? ' ha prenotato e PAGATO un ordine' :' ha prenotato un ordine!'}}</h1>
            @elseif($content_mail['to'] == 'user' && ($content_mail['status'] == 2 || $content_mail['status'] == 3))
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, grazie per aver prenotato tramite il nostro sito web!</h1>
                <h4 style="font-size: 16px; line-height: 1.8; margin: 5px;">Il tuo ordine è nella nostra coda, a breve riceverai l'esito del processamento</h4>
            @elseif($content_mail['to'] == 'user' && ($content_mail['status'] == 1 || $content_mail['status'] == 5))
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, ti informiamo che il tuo ordine è stato confermato!</h1>

            @elseif($content_mail['to'] == 'user' && $content_mail['status'] == 0)
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, ci dispiace informarti che il tuo ordine è stato annullato!</h1>
            @elseif($content_mail['to'] == 'user' && in_array($content_mail['status'], [0, 6]))
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, ci dispiace informarti che il tuo ordine !{{$content_mail['status'] == 6 ? ' è stato annullato e rimborsato' :' è stato annullato'}}</h1>
            @endif

            <!-- Data prenotata -->
            <p style="font-size: 16px; line-height: 1.8; margin: 5px;">Data prenotata: {{ $content_mail['date_slot'] }}</p>
            
            <!-- Elenco prodotti -->
            <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">I prodotti:</h3>
            <div style="width: 100%;">
                
                @foreach ($content_mail['cart'] as $i)               
                    <?php
                        $arrO= json_decode($i->pivot->option); 
                        $arrA= json_decode($i->pivot->add); 
                        $arrD= json_decode($i->pivot->remove); 
                        // dd($i->pivot->option);
                        // dd($i->pivot->add);
                        //dd($i->pivot->quantity);

                    ?>
                    <div style="width: 100%; margin: 5px 0;">
                        <span style="font-size: 18px; font-weight: bold;">* {{$i->pivot->quantity}}</span>
                        <span style="font-size: 18px; font-weight: bold; margin-left: 10px;">{{$i->name}}</span>
                        <span style="font-size: 16px; line-height: 1.8; margin-left: 10px;">€{{$i->price / 100 }}</span>
                    </div>
                    <br>
                    <div style="margin: 5px;">
                        <!-- Opzioni prodotto -->
                        @if (count($arrO))
                            <div style="margin: 5px;">
                                <h5 style="font-size: 16px; line-height: 1.8; margin: 5px 0;">Opzioni:</h5>
                                @foreach ($arrO as $a)
                                    <span style="font-size: 16px; line-height: 1.8; margin: 2px 0;">+ {{$a}} </span>
                                @endforeach
                            </div>
                        @endif
                        <div style="margin: 5px;">
                            <!-- Ingredienti extra -->
                            @if (count($arrA))
                                <div style="margin: 5px;">
                                    <h5 style="font-size: 16px; line-height: 1.8; margin: 5px 0;">Ingredienti extra:</h5>
                                    @foreach ($arrA as $a)
                                        <span style="font-size: 16px; line-height: 1.8; margin: 2px 0;">+ {{$a}}</span>
                                    @endforeach
                                </div>
                            @endif
                            <!-- Ingredienti rimossi -->
                            @if (count($arrD))
                                <div style="margin: 5px;">
                                    <h5 style="font-size: 16px; line-height: 1.8; margin: 5px 0;">Ingredienti rimossi:</h5>
                                    @foreach ($arrD as $a)
                                        <span style="font-size: 16px; line-height: 1.8; margin: 2px 0;">- {{$a}}</span>
                                    @endforeach       
                                </div>
                            @endif
                        </div>
                    </div>
                    <hr style="height: 2px; background-color: rgb(75, 81, 88); border: none; margin: 10px 0; order-radius: 20px">
                @endforeach
               
            </div>

            <!-- Indirizzo per la consegna -->
            @if (isset($content_mail['comune']))
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Indirizzo per la consegna:</h3>
                <p style="font-size: 16px; line-height: 1.8; margin: 5px;">{{$content_mail['address']}}, {{$content_mail['address_n']}}, {{$content_mail['comune']}}</p>
                <p style="font-size: 16px; line-height: 1.8; margin: 5px;">L'importo verra pagato al momento della consegna.</p>
            @endif

            <!-- Totale carrello -->
            <h4 style="font-size: 16px; line-height: 1.8; margin: 5px;">Totale carrello: €{{$content_mail['total_price'] / 100}}</h4>
        
        @elseif($content_mail['type'] == 'res')
            <!-- Messaggi per tipo 'res' -->
            @if ($content_mail['to'] == 'admin')
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Il sign/gr {{ $content_mail['name'] }}, ha prenotato un tavolo!</h1>
            @elseif($content_mail['to'] == 'user' && $content_mail['status'] == 2)        
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, grazie per aver prenotato un tavolo tramite il nostro sito web!</h1>
                <h4 style="font-size: 16px; line-height: 1.8; margin: 5px;">La tua prenotazione è nella nostra coda, a breve riceverai l'esito del processamento</h4>
            @elseif($content_mail['to'] == 'user' && $content_mail['status'] == 1)
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, ti informiamo che la tua prenotazione è stata confermata!</h1>
            @elseif($content_mail['to'] == 'user' && $content_mail['status'] == 0)
                <h1 style="color: #d35400; font-size: 24px; text-align: center; margin: 5px;">Ciao {{ $content_mail['name'] }}, ci dispiace informarti che la tua prenotazione è stata annullata!</h1>
            @endif

            <!-- Sala prenotata (se applicabile) -->
            @if (config('configurazione.double_t') && $content_mail['sala'] !== 0)
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Sala prenota: <strong>{{$content_mail['sala'] == 1 ? config('configurazione.set_time_dt')[0] : config('configurazione.set_time_dt')[1]}}</strong></h3>
            @endif

            <!-- Data prenotata -->
            <p style="font-size: 16px; line-height: 1.8; margin: 5px;">Data prenotata: {{ $content_mail['date_slot'] }}</p>

            <!-- Numero di persone -->
            @if (is_string($content_mail['n_person']))
                @php $n_person = json_decode($content_mail['n_person'], true); @endphp
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Numero di adulti: {{ $n_person['adult'] }}</h3>
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Numero di bambini: {{ $n_person['child'] }}</h3>
            @else
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Numero di adulti: {{ $content_mail['n_person']['adult'] }}</h3>
                <h3 style="font-size: 16px; line-height: 1.8; margin: 10px 0;">Numero di bambini: {{ $content_mail['n_person']['child'] }}</h3>
            @endif
        @endif

        <!-- Messaggio opzionale -->
        @if($content_mail['message'] !== NULL)
            <h4 style="font-size: 16px; line-height: 1.8; margin: 5px;">Messaggio:</h4>
            <p style="font-size: 16px; line-height: 1.8; margin: 5px;">{{$content_mail['message']}}</p>
        @endif

        <!-- Se destinatario è admin -->
        @if($content_mail['to'] == 'admin')             
            <!-- Bottone per chiamare -->
            <a href="tel:{{$content_mail['phone']}}" style="display: block; width: 80%; text-align: center; padding: 10px; background-color: #119b1a; color: white; text-decoration: none; border-radius: 5px; margin: 20px auto 0 auto;">Chiama {{$content_mail['name']}}</a>
            <!-- Bottone per visualizzare nella dashboard -->
            @if ($content_mail['type'] == 'or')
                {{-- <form action="" method="POST"> --}}
                {{-- <a href="{{config('configurazione.APP_URL')}}/api/orders/status?id={{$content_mail['order_id']}}&c_a=true" style="display: block; width: 80%; text-align: center; padding: 10px; background-color: #0a5c2d; color: white; text-decoration: none; border-radius: 5px; margin: 20px auto 0 auto;">Inoltra su WA e conferma</a> --}}
                <a href="{{config('configurazione.APP_URL')}}/admin/orders/{{$content_mail['order_id']}}" style="display: block; width: 80%; text-align: center; padding: 10px; background-color: #11289b; color: white; text-decoration: none; border-radius: 5px; margin: 20px auto 0 auto;">Visualizza nella Dashboard</a>
            @elseif($content_mail['type'] == 'res')
                {{-- <form action="{{config('configurazione.APP_URL') }}api/reservations/status" method="POST">
                    @csrf
                    <input value="0" type="hidden" name="wa">
                    <input value="1" type="hidden" name="c_a">
                    <input value="1" type="hidden" name="wa_group">
                    <input value="{{$content_mail['res_id']}}" type="hidden" name="id">
                    <button type="submit" style="font-size: 16px; line-height: 1.8; margin: 5px;">Conferma e inoltra nel gruppo</button>
                </form> --}}
                <a href="{{config('configurazione.APP_URL')}}/admin/reservations/{{$content_mail['res_id']}}" style="display: block; width: 80%; text-align: center; padding: 10px; background-color: #11289b; color: white; text-decoration: none; border-radius: 5px; margin: 20px auto 0 auto;">Visualizza nella Dashboard</a>
            @endif
        @endif

        
    </div>
    <!-- Footer -->
    <div style="width: 95%; margin: 50px auto 0; background-color: black; color: white; padding: 10px; text-align: center; font-size: 12px;">
        @if ($content_mail['to'] == 'user' && $content_mail['status'] !== 0)
            <p style="font-size: 12px; line-height: 1.5; margin: 5px;">
                Contatta {{config('configurazione.APP_NAME')}} se desideri annullare o modificare la tua prenotazione:
            </p>
            <p style="line-height: 1.5; margin: 15px;">
                <a href="tel:{{$content_mail['admin_phone']}}" style="background-color: #ffffff; color: rgb(0, 0, 0); padding: 8px 12px; text-align: center; text-decoration: none; border-radius: 35px; font-size: 18px;">Chiama {{config('configurazione.APP_NAME')}}</a>
            </p>
        @endif
        <p style="font-size: 12px; line-height: 1.5; margin: 5px;">&copy; 2024 {{ config('configurazione.APP_NAME') }}. Tutti i diritti riservati.</p>
        <p style="font-size: 12px; line-height: 1.5; margin: 5px;" > Powered by <a style="color: white; text-decoration: none" href="https://future-plus.it">Future +</a></p>
    </div>
    
</body>
</html>
