<?php

namespace Database\Seeders;

use DateTime;
use App\Models\Day;
use App\Models\Date;
use App\Models\Month;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatesTableSeeder extends Seeder
{

    protected $reserving;
    protected $availability;
    protected $time;
    protected $variable;
    protected $days_on;

    public function setVariables($reserving, $availability, $time, $days_on)
    {
        $this->reserving = $reserving;
        $this->availability = $availability;
        $this->time = $time;
        $this->days_on = $days_on;
    }

    public function run()
    {
        
        $currentDate = new DateTime();
        $reserving = $this->reserving;
        $availability = $this->availability;
        $times = $this->time;
        $abledDays = $this->days_on;

        $currentMounth = $currentDate->format('n');

        for ($i = 1; $i <= 12; $i++) {
            $daysInMonth = $currentDate->format('t'); //n giorni nel mese

            for ($day = $currentDate->format('d'); $day <= $daysInMonth; $day++) {
                $currentDayOfWeek = $currentDate->format('N'); //giorno della settimana in numero 1-7

                $add = false;
                foreach ($times[$currentDayOfWeek - 1] as $time) {
                    
                    
                    if ($time['set']) {
                        $add = true;
                        
                        if( config('configurazione.double_t')){
                            if( config('configurazione.pack') == 2 ){ 
                                $visible = [
                                    'table_1' => $time['set'] == 1 ? 1 : 0,
                                    'table_2' => $time['set'] == 1 ? 1 : 0,
                                ];
                            }elseif( config('configurazione.pack') == 3){
                                if(config('configurazione.typeOfOrdering')){
                                    $visible = [
                                        'cucina_1' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'cucina_2' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }else{
                                    $visible = [
                                        'asporto' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }
                            }elseif( config('configurazione.pack') == 4){     
                                if(config('configurazione.typeOfOrdering')){
                                    $visible = [
                                        'table_1' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'table_2' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'cucina_1' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'cucina_2' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }else{
                                    $visible = [
                                        'table_1' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'table_2' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'asporto' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }
                            }
                        }else{
                            if( config('configurazione.pack') == 2 ){ 
                                $visible = [
                                    'table' => $time['set'] == 1 ? 1 : 0,
                                ];
                            }elseif( config('configurazione.pack') == 3){
                                if(config('configurazione.typeOfOrdering')){
                                    $visible = [
                                        'cucina_1' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'cucina_2' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }else{
                                    $visible = [
                                        'asporto' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }
                            }elseif( config('configurazione.pack') == 4){     
                                if(config('configurazione.typeOfOrdering')){
                                    $visible = [
                                        'table' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'cucina_1' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'cucina_2' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }else{
                                    $visible = [
                                        'table' => ($time['set'] == 2 || $time['set'] == 3 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays) ? 1 : 0,
                                        'asporto' => (($time['set'] == 1 || $time['set'] == 3 || $time['set'] == 4 || $time['set'] == 5 || $time['set'] == 6 || $time['set'] == 7) && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                        'domicilio' => ($time['set'] >= 4 && $time['set'] <= 7 && in_array($currentDayOfWeek, $abledDays)) ? 1 : 0,
                                    ];
                                    
                                }
                            }
                        }
                        

                        Date::create([  
                            'year' => $currentDate->format('Y'),
                            'month' => $currentDate->format('n'),
                            'day' => $currentDate->format('d'),
                            'day_w' => $currentDayOfWeek,
                            'time' => $time['time'],
                            'date_slot' => $currentDate->format('d') . '/' .  $currentDate->format('m') . '/' .  $currentDate->format('Y') . ' ' . $time['time'],

                            'status' => $time['set'],
                            
                            'reserving' => json_encode($reserving),
                            'visible' => json_encode($visible),
                            'availability' => json_encode($availability),

                        ]);
                    }
                }
                if(!$add){
                    Date::create([  
                        'year' => $currentDate->format('Y'),
                        'month' => $currentDate->format('n'),
                        'day' => $currentDate->format('d'),
                        'day_w' => $currentDayOfWeek,
                        'time' => 0,
                        'date_slot' => $currentDate->format('d') . '/' .  $currentDate->format('m') . '/' .  $currentDate->format('Y') . ' ' . 'null.',
                        'status' => 0,
                        'reserving' => 0,
                        'visible' => 0,
                        'availability' => 0,
                    ]);
                }
                $currentDate->modify('+1 day');
            }
        }
        // dump($currentDate);
    }
}

