<?php

namespace App\Http\Controllers\Admin;

use DateTime;
use Carbon\Carbon;
use Stripe\Refund;
use Stripe\Stripe;
use App\Models\Date;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Ingredient;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    

    public function filter(Request $request){
        
        // FUNZIONE DI FILTRAGGIO INDEX
        $status = $request->input('status');
        $name = $request->input('name');
        $order = $request->input('order');
        $date = $request->input('date');

        $filters = [
            'name'          => $name ,
            'status'        => $status ,
            'date'          => $date ,
            'order'         => $order,     
        ];
        //dd($date);
        $query = Order::query();
       
        if ($name) {
            $query->where('name', 'like', '%' . $name . '%')
            ->orWhere('surname', 'like', '%' . $name . '%');
        } 
        if ($status == 4) {
            $query->where('status', 0)
            ->orWhere('status', 6);
        } else if ($status == 1) {
            $query->where('status', 1)
            ->orWhere('status', 5);
        } else if ($status == 2) {
            $query->where('status', 2)
            ->orWhere('status', 3);
        } else if ($status == 5) {
            $query->where('status', 3)
            ->orWhere('status', 5);
        }else{ 
            $query->where('status', '!=', 4);
        }
        if($date){
            $formattedDate = DateTime::createFromFormat('Y-m-d', $date)->format('d/m/Y');

            $query->where('date_slot', 'like', '%' . $formattedDate . '%');
        }
        if($order){
            $orders = $query->orderBy('date_slot', 'asc')->get();
        }else{
            $orders = $query->orderBy('created_at', 'desc')->get();    
        }        
    

        $data = [];
        array_push($data, $filters);
        array_push($data, $orders);

        return redirect()->back()->with('filter', $data);
    }

    protected function statusF($wa, $c_a, $id){

        $order = Order::where('id', $id)->with('products')->firstOrFail();
        //dd($order);
        if($c_a){
            if($order->status == 2 || $order->status == 0){
                $order->status = 1;
            }elseif($order->status == 3){
                $order->status = 5;
            }
            $m = 'L\'ordine è stata confermato correttamente';
            $message = 'Grazie ' . $order->name . ' per aver ordinato da noi, ti confermiamo che il tuo ordine sarà pronto per il ' . $order->date_slot;    
        }else{
            if(in_array($order->status, [3, 5])){
                $m = 'L\'ordine è stata annullato e RIMBORSATO correttamente';
                $message = 'Ci dispiace informarti che purtroppo il tuo ordine è stato annullato e rimborsato';
                //codice per rimborso
                try {
                    $stripeSecretKey = config('configurazione.STRIPE_SECRET'); 
                 
                    // Imposta la chiave segreta di Stripe
                    Stripe::setApiKey($stripeSecretKey);
        
                    if ($order->checkout_session_id === null) {
                        return response()->json(['error' => 'Payment not found'], 404);
                    }
        
                    // Effettua il rimborso
                    $refund = Refund::create([
                        'payment_intent' => $order->checkout_session_id, // Questo è l'ID dell'intent di pagamento
                    ]);
        
                    // Aggiorna lo stato del rimborso nella tua tabella
                    $order->status = 6;
        
                    
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }
                
            }elseif(in_array($order->status, [2, 1])){
                $m = 'L\'ordine è stata annullato correttamente';
                $message = 'Ci dispiace informarti che purtroppo il tuo ordine è stato annullato';
                $order->status = 0;
            }else{
                $m = 'L\'ordine erag gia stato annullato!';
                return redirect()->back()->with('success', $m); 
            }
            $date = Date::where('date_slot', $order->date_slot)->firstOrFail();
            $vis = json_decode($date->visible, 1); 
            $reserving = json_decode($date->reserving, 1);
            if(config('configurazione.typeOfOrdering')){
                $np_cucina_1 = 0;
                $np_cucina_2 = 0;
                foreach ($order->products as $p) {
                    $qt = 0;
                    $op = OrderProduct::where('product_id', $p->id)->where('order_id', $order->id)->firstOrFail();
                    if($op !== null){
                        $qt = $op->quantity;
                        if($p->type_plate == 1 && $qt !== 0){
                            $np_cucina_1 += ($p->slot_plate * $qt);
                            if($vis['cucina_1'] == 0){
                                $vis['cucina_1'] = 1;
                            }
                        }
                        if($p->type_plate == 2){
                            $np_cucina_2 += ($p->slot_plate * $qt);
                            if($vis['cucina_2'] == 0){
                                $vis['cucina_2'] = 1;
                            }
                        }
                    }
                }
                $reserving['cucina_1'] = $reserving['cucina_1'] - $np_cucina_1;
                $reserving['cucina_2'] = $reserving['cucina_2'] - $np_cucina_2;
                if($order->address !== null){
                    if($vis['domicilio'] == 0){
                        $vis['domicilio'] = 1;
                    }
                    $reserving['domicilio'] --;
                }
            }else{
                if($order->address !== null){
                    if($vis['domicilio'] == 0){
                        $vis['domicilio'] = 1;
                    }
                    if($vis['asporto'] == 0){
                        $vis['asporto'] = 1;
                    }
                    $reserving['domicilio'] --;
                }else{
                    if($vis['asporto'] == 0){
                        $vis['asporto'] = 1;
                    }
                    $reserving['asporto'] --;

                }
            }

            $date->reserving = json_encode($reserving);
            $date->visible = json_encode($vis);
            $date->update();

            
            
        }
        $order->update();
        

        $set = Setting::where('name', 'Contatti')->firstOrFail();
        $p_set = json_decode($set->property, true);
        $bodymail = [
            'type' => 'or',
            'to' => 'user',
            'order_id' => $order->id,
            'name' => $order->name,
            'surname' => $order->surname,
            'email' => $order->email,
            'date_slot' => $order->date_slot,
            'message' => $order->message,
            'phone' => $order->phone,
            'admin_phone' => $p_set['telefono'],
            
            'comune' => $order->comune,
            'address' => $order->address,
            'address_n' => $order->address_n,
            
            'status' => $order->status,
            'cart' => $order->products,
            'total_price' => $order->tot_price,
        ];

       
        $mail = new confermaOrdineAdmin($bodymail);
        Mail::to($order['email'])->send($mail);

       
        if($wa){
            return redirect("https://wa.me/39" . $order->phone . "?text=" . $message);
        }
        return $m;
    }

    public function status(Request $request){
        $wa = $request->input('wa');
        $c_a = $request->input('c_a');
        $id = $request->input('id');
        
        $m = $this->statusF($wa, $c_a, $id);
        
        return redirect()->back()->with('success', $m);   
    }
    public function status_mail(){
        $id = request()->query('id');
        $c_a = request()->query('c_a');
        $wa = false;

        $this->statusF($wa, $c_a, $id);
        

//...
        // dump("https://chat.whatsapp.com/" . config('configurazione.id_group') . "?text=" . $info);
        // return redirect("https://wa.me/" . config('configurazione.id_group') . "?text=" . $info);
        //return redirect("https://chat.whatsapp.com/" . config('configurazione.id_group') . "?text=" . $info);
    }

    public function index()
    {
        $order_remove = Order::where('status', 4)
            ->where('created_at', '<', Carbon::now()->subHours(2))
            ->get();
            if (!$order_remove->isEmpty()) {
                foreach ($order_remove as $k) {
                    $k->delete();
                }
            }
        $query = Order::whereRaw("DATE(STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i')) >= ?", [now()->toDateString()]);

        $orders = $query->where('status', '!=', 4)->orderBy('date_slot', 'asc')->get();
        return view('admin.Orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::where('id', $id)->with('products')->firstOrFail();
        $orderProduct = OrderProduct::all();
        $cart = 0;
        foreach ($order->products as $o) {
            $add = json_decode( $o->pivot->add , 1);
            $option = json_decode( $o->pivot->option , 1);
            foreach ($add as $a) {
                $ing = Ingredient::where('name', $a)->first();
                $cart += $ing->price * $o->pivot->quantity;
            }
            foreach ($option as $a) {
                $ing = Ingredient::where('name', $a)->first();
                $cart += $ing->price * $o->pivot->quantity;
            }
            $cart += $o->price * $o->pivot->quantity;
        }
        $delivery_cost = $order->tot_price - $cart;
        

        return view('admin.Orders.show', compact('order', 'orderProduct', 'delivery_cost'));
    }

    
    public function destroy($id)
    {
        //
    }
    
}
