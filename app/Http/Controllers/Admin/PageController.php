<?php



namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Date;
use App\Models\Post;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function dashboard() {
        $dates = Date::all();
        $setting = Setting::all();
        $product_ = [
            1 => Product::where('visible', 1)->where('archived', 0)->count(),
            2 => Product::where('archived', 1)->count(),
        ];
        $stat = [
            1 => Category::count(),
            2 => Ingredient::count(),
        ];
        $meseCorrenteInizio = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $meseCorrenteFine = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
        $traguard = [
            1 =>  Order::whereBetween(DB::raw("STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i')"), [$meseCorrenteInizio, $meseCorrenteFine])->sum('tot_price'),
            2 =>  Order::sum('tot_price'),
            3 =>  Reservation::selectRaw('SUM(JSON_EXTRACT(n_person, "$.adult") + JSON_EXTRACT(n_person, "$.child")) AS total_persons')
                ->whereBetween(DB::raw("STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i')"), [
                    Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'),
                    Carbon::now()->endOfMonth()->format('Y-m-d H:i:s')
                ])
                ->value('total_persons'),
            4 =>  Reservation::selectRaw('SUM(JSON_EXTRACT(n_person, "$.adult") + JSON_EXTRACT(n_person, "$.child")) AS total_persons')
                ->whereBetween(DB::raw("STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i')"), [
                    Carbon::now()->startOfYear()->format('Y-m-d H:i:s'),
                    Carbon::now()->endOfYear()->format('Y-m-d H:i:s')
                ])
                ->value('total_persons')
        ];
        $post = [ 
            1 => Post::count(),
            2 => Post::where('visible', 0)->count(),
            3 => Post::where('visible', 1)->where('archived', 0)->count(),
            4 => Post::where('archived', 1)->count(),
        ];
        $order = [ 
            1 => Order::where('status', 1)->count() + Order::where('status', 5)->count(),
            2 => Order::where('status', 2)->count(),
            3 => Order::where('status', 0)->count() + Order::where('status', 6)->count(),
            4 => Order::where('status', 3)->count(),
        ];
        $reservation = [
            1 => Reservation::where('status', 1)->count() + Reservation::where('status', 5)->count(),
            2 => Reservation::where('status', 2)->count(),
            3 => Reservation::where('status', 0)->count(),
            4 => Reservation::where('status', 3)->count(),
        ];
        

        if(count($dates) == 0){
            return view('admin.dashboard', compact('setting', 'stat', 'product_', 'traguard', 'order', 'reservation', 'post'));
        };
        $year = [
            1 => [
                'year' => $dates[0]['year'],
                'month'=> $dates[0]['month'],
                'days'=> [],
            ]
        ];
        //dd($dates[1]);
        $firstDay = [
            'year' => $dates[0]['year'],
            'month' => $dates[0]['month'],
            'day' => $dates[0]['day'],
        ];
               
        foreach ($dates as $d) {
            list($date, $time) = explode(" ", $d['date_slot']);
            
            if($d['reserving'] !== '0'){
                $res = json_decode($d['reserving'], 1);
                // dump($date . $time);
                // dump($res);
                if( config('configurazione.double_t')){
                    if( config('configurazione.pack') == 2 ){        
                        $day = [
                            'day' => $d['day'],
                            'day_w' => $d['day_w'],
                            'date' => $date,
                            'time' => [],
                            
                            'table' => $res['table_1'] + $res['table_2'],
                        ];
                        $time = [
                            'time' => $d['time'],
                            
                            'table' => $res['table_1'] + $res['table_2'],
                        ];
                    }elseif( config('configurazione.pack') == 3){
                        if(config('configurazione.typeOfOrdering')){
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
                                
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
                                
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }else{
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
                                
                                'asporto' => $res['asporto'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
                                
                                'asporto' => $res['asporto'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }
                    }elseif( config('configurazione.pack') == 4){
                        if(config('configurazione.typeOfOrdering')){ 
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
                                
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'table' => $res['table_1'] + $res['table_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
                                
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'table' => $res['table_1'] + $res['table_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }else{
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
                                
                                'asporto' => $res['asporto'],
                                'table' => $res['table_1'] + $res['table_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
                                
                                'asporto' => $res['asporto'],
                                'table' => $res['table_1'] + $res['table_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }
                    }
                }else{
                    if( config('configurazione.pack') == 2 ){        
                        $day = [
                            'day' => $d['day'],
                            'day_w' => $d['day_w'],
                            'date' => $date,
                            'time' => [],
    
                            'table' => $res['table'],
                        ];
                        $time = [
                            'time' => $d['time'],
    
                            'table' => $res['table'],
                        ];
                    }elseif( config('configurazione.pack') == 3){
                        if(config('configurazione.typeOfOrdering')){
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
    
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
    
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }else{
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
    
                                'asporto' => $res['asporto'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
    
                                'asporto' => $res['asporto'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }
                    }elseif( config('configurazione.pack') == 4){
                        if(config('configurazione.typeOfOrdering')){ 
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
    
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'table' => $res['table'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
    
                                'asporto' => $res['cucina_1'] + $res['cucina_2'],
                                'table' => $res['table'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }else{
                            $day = [
                                'day' => $d['day'],
                                'day_w' => $d['day_w'],
                                'date' => $date,
                                'time' => [],
    
                                'asporto' => $res['asporto'],
                                'table' => $res['table'],
                                'domicilio' => $res['domicilio'],
                            ];
                            $time = [
                                'time' => $d['time'],
    
                                'asporto' => $res['asporto'],
                                'table' => $res['table'],
                                'domicilio' => $res['domicilio'],
                            ];
                        }
                    }

                }
            }
            
            $cy = count($year);
            if($d['year'] == $firstDay['year'] && $d['month'] == $firstDay['month']){
                if( $d['time'] == 0 ){
                    array_push($year[$cy]['days'], $dayoff = [
                        'day' => $d['day'],
                        'day_w' => $d['day_w'],
                        'date' => $date]);
                }elseif($d['day'] !== $firstDay['day'] || count($year[1]['days']) == 0){
                    array_push($year[$cy]['days'], $day);
                    array_push($year[$cy]['days'][count($year[$cy]['days']) - 1]['time'], $time);
                }elseif($d['day'] == $firstDay['day']){
                    if( config('configurazione.pack') == 2 ){        
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['table'] += $day['table'];        
                    }elseif( config('configurazione.pack') == 3){
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['asporto'] += $day['asporto'];
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['domicilio'] += $day['domicilio'];
                        
                    }elseif( config('configurazione.pack') == 4){
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['table'] += $day['table'];
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['domicilio'] += $day['domicilio'];
                        $year[$cy]['days'][count($year[$cy]['days']) - 1]['asporto'] += $day['asporto'];
                        
                    }
                    array_push($year[$cy]['days'][count($year[$cy]['days']) - 1]['time'], $time);
                }
            }else{
                
                $month = [
                    'year' =>  $d['year'],
                    'month' => $d['month'],
                    'days' => [],
                ];
                if($d['reserving'] !== '0'){
                    array_push($month['days'], $day);
                }else{
                    array_push($month['days'], $dayoff = [
                        'day' => $d['day'],
                        'day_w' => $d['day_w'],
                        'date' => $date]);
                }
                array_push($year, $month);
            }
            $firstDay = [
                'year' => $d['year'],
                'month' => $d['month'],
                'day' => $d['day'],
            ];
            
        };
        
       // dd($year);
        return view ('admin.dashboard', compact('year', 'setting', 'stat', 'product_', 'traguard', 'order', 'reservation', 'post'));
    }
    public function statistics()
    {
        // Grafico a torta: Prodotti più ordinati
        $topProducts = DB::table('order_product')
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->take(10) // Puoi variare questo valore per vedere più prodotti
            ->get()
            ->mapWithKeys(function ($item) {
                $product = Product::find($item->product_id);
                return [$product->name => $item->total_quantity];
            });

        // Grafico a colonne: Ordinazioni nel tempo
        $ordersOverTime = DB::table('order_product')
        ->join('orders', 'order_product.order_id', '=', 'orders.id')
        ->join('products', 'order_product.product_id', '=', 'products.id')
        ->select(
            DB::raw("DATE_FORMAT(STR_TO_DATE(orders.date_slot, '%d/%m/%Y %H:%i'), '%Y-%m-%d') as day"), 
            'products.name', 
            DB::raw('SUM(order_product.quantity) as quantity')
        )
        ->groupBy('day', 'products.name')
        ->orderBy('day')
        ->get();

        // Grafico a linee: Ricavi nel tempo
        $revenueOverTime = Order::select(DB::raw("DATE_FORMAT(STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i'), '%Y-%m') as month"), DB::raw('SUM(tot_price) as total_revenue'))
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->pluck('total_revenue', 'month');

        $reservationsOverTime = DB::table('reservations')
        ->select(
            DB::raw("UNIX_TIMESTAMP(STR_TO_DATE(date_slot, '%d/%m')) as timestamp"), // Timestamp UNIX
            DB::raw("DATE_FORMAT(STR_TO_DATE(date_slot, '%d/%m'), '%d %M, %W') as formatted_date"), // Data formattata
            DB::raw("COALESCE(SUM(JSON_EXTRACT(n_person, '$.adult')), 0) as total_adults"), // Totale adulti
            DB::raw("COALESCE(SUM(JSON_EXTRACT(n_person, '$.child')), 0) as total_children") // Totale bambini
        )
        ->groupBy( 'timestamp', 'formatted_date') // Raggruppa per timestamp e data formattata
        ->orderBy('timestamp', 'asc') // Ordina per timestamp
        ->get();
        
        

        

        return view('admin.statistics', [
            'topProducts' => $topProducts,
            'ordersOverTime' => $ordersOverTime,
            'revenueOverTime' => $revenueOverTime,
            'reservationsOverTime' => $reservationsOverTime,
        ]);
    }
    public function sendNotification()
    {
        // Imposta le intestazioni per SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        // Mantieni attivo il ciclo per continuare a inviare dati
        while (true) {
            if (connection_aborted()) {
                break; // Esce dal ciclo se la connessione viene interrotta
            }
            
            // Ottieni ordini non notificati
            $order = Order::where('notificated', 0)->where('status', '!=', 4)->get();
            $res = Reservation::where('notificated', 0)->where('status', '!=', 4)->get();
    
            $eventData = [];
            if (count($order) || count($res)) {
                if (count($order)){
                    foreach ($order as $o) {
                        $eventData[] = [
                            'set'  => 'or',
                            'name'  => $o->name,
                            'data'  => $o->date_slot,
                            'price'  => $o->tot_price / 100,
                        ];
                        // Imposta notificato a 1 per evitare notifiche duplicate
                        $o->notificated = 1;
                        $o->update();
                    }
                }
                if (count($res)){
                    foreach ($res as $o) {
                        $person = json_decode($o->n_person, 1);
                        $eventData[] = [
                            'set'  => 'res',
                            'name'  => $o->name,
                            'data'  => $o->date_slot,
                            'adult'  => $person['adult'],
                            'child'  => $person['child'],
                        ];
                        // Imposta notificato a 1 per evitare notifiche duplicate
                        $o->notificated = 1;
                        $o->update();
                    }
                }
                // Invia i dati formattati secondo lo standard SSE
                echo 'data: ' . json_encode($eventData) . "\n\n";
                
                // Forza l'invio immediato dei dati al client
                ob_flush();
                flush();
            }
    
            // Intervallo di attesa per ridurre il carico sul server
            //sleep(7); // 5 secondi di pausa tra le verifiche
        }
    }    

}

