<?php

namespace App\Http\Controllers\Admin;

use DateTime;
use App\Models\Date;
use App\Models\Setting;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class ReservationController extends Controller
{
    
    public function status(Request $request){
        $wa = $request->input('wa');
        $c_a = $request->input('c_a');
        $id = $request->input('id');
        $res = Reservation::where('id', $id)->firstOrFail();
        if($c_a){
            $res->status = 1;
            $m = 'La prenotazione e\' stata confermata correttamente';
            $message = 'Siamo felici di informarti che la tua prenotazione e\' stata confermata, ti ricordo la data e l\'orario che hai scelto: ' . $res->date_slot ;
        }else{
            if($res->status == 0){
                $m = 'La prenotazione e\' stata gia annullata correttamente';
                return redirect()->back()->with('success', $m);
            }
            $date = Date::where('date_slot', $res->date_slot)->firstOrFail();
            $vis = json_decode($date->visible, 1); 
            $reserving = json_decode($date->reserving, 1);
            $_p = json_decode($res->n_person);
            $tot_p = $_p->child + $_p->adult;
            if(config('configurazione.double_t')){
                if($res->sala == 1){
                    if($vis['table_1'] == 0){
                        $vis['table_1'] = 1;
                    }
                    $reserving['table_1'] = $reserving['table_1'] - $tot_p;
                    $date->reserving = json_encode($reserving);
                    $date->visible = json_encode($vis);
                    $date->update();
                }else{
                    if($vis['table_2'] == 0){
                        $vis['table_2'] = 1;
                    }
                    $reserving['table_2'] = $reserving['table_2'] - $tot_p;
                    $date->reserving = json_encode($reserving);
                    $date->visible = json_encode($vis);
                    $date->update();
                }
            }else{
                if($vis['table'] == 0){
                    $vis['table'] = 1;
                }
                $reserving['table'] = $reserving['table'] - $tot_p;
                $date->reserving = json_encode($reserving);
                $date->visible = json_encode($vis);
                $date->update();
            }

            $res->status = 0;
            $m = 'La prenotazione e\' stata annullata correttamente';
            $message = 'Ci spiace informarti che la tua prenotazione e\' stata annullata per la data e l\'orario che hai scelto... ' . $res->date_slot ;
        }
        $res->update();
        

        $set = Setting::where('name', 'Contatti')->firstOrFail();
        $p_set = json_decode($set->property, true);
        $bodymail = [
            'type' => 'res',
            'to' => 'user',

            'res_id' => $res->id,
            'name' => $res->name,
            'surname' => $res->surname,
            'email' => $res->email,
            'date_slot' => $res->date_slot,
            'message' => $res->message,
            'sala' => $res->sala,
            'phone' => $res->phone,
            'admin_phone' => $p_set['telefono'],
               
            'n_person' => $res->n_person,
            'status' => $res->status,
        ];

       
        $mail = new confermaOrdineAdmin($bodymail);

        Mail::to($res['email'])->send($mail);
        if($wa){
            return redirect("https://wa.me/39" . $res->phone . "?text=" . $message);
        }
        return redirect()->back()->with('success', $m);   
    }
    

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
        
        $query = Reservation::query();
       
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
            $reservations = $query->orderBy('updated_at', 'desc')->get();    
        }else{
            $reservations = $query->orderBy('date_slot', 'desc')->get();
        }        
    
        $data = [];
        array_push($data, $filters);
        array_push($data, $reservations);
      
        
        return redirect()->back()->with('filter', $data);
    }




    public function index()
    {
        $query = Reservation::whereRaw("DATE(STR_TO_DATE(date_slot, '%d/%m/%Y %H:%i')) >= ?", [now()->toDateString()]);

        $reservations = $query->where('status', '!=', 4)->orderBy('date_slot', 'asc')->get();
        return view('admin.Reservations.index', compact('reservations'));
    }

    public function show($id)
    {
        $reservation = Reservation::where('id', $id)->firstOrFail();

        return view('admin.Reservations.show', compact('reservation'));
    }

    public function destroy($id)
    {
        //
    }
}
