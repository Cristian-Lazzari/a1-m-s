<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SettingsTableSeeder extends Seeder
{
   
    public function run()
    {
        
        
        if(config('configurazione.pack') > 1 ){
            $settings = [
                [
                    'name' => 'Prenotaione Tavoli',  
                    'status' => 1,
                    'property' => [
                        'empty' => 0
                    ],
                ],
                [
                    'name' => 'Prenotazione Asporti',  
                    'status' => 1,
                    'property' => [
                        'pay' => 1, // 0 dis - 1 ab - 2 ob
                        'min_price' => 0,
                    ],
                ],
                [
                    'name' => 'Periodo di Ferie',  
                    'status' => 0,
                    'property' => [
                        'from' => '',
                        'to' => '',
                    ]
                ],
                [
                    'name' => 'Orari di attività',
                    'property' => []
                ],
                [
                    'name' => 'Posizione',
                    'property' => []
                ],
                [
                    'name' => 'Contatti',
                    'property' => []
                ],
                [
                    'name' => 'Possibilità di consegna a domicilio',  
                    'status' => 1,
                    'property' => [
                        'pay' => 1, // 0 dis - 1 ab - 2 ob
                        'min_price' => 0,
                        'delivery_cost' => 0,
                    ],
                ],
                [
                    'name' => 'Comuni per il domicilio',
                    'property' => [
                        [
                            'comune' => 'Monte San Vito',
                            'provincia' => 'AN',
                        ]
                    ]
                ],
                [
                    'name' => 'wa',
                    'property' => [
                        'last_response_wa' => ''
                    ]
                ],
            ];
        }else{
            $settings = [
                [
                    'name' => 'Prenotaione Tavoli',  
                    'status' => 1,
                    'property' => [
                        'empty' => 0
                    ],
                ],
                [
                    'name' => 'Prenotazione Asporti',  
                    'status' => 1,
                    'property' => [
                        'pay' => 1, // 0 dis - 1 ab - 2 ob
                        'min_price' => 0,
                    ],
                ],
                [
                    'name' => 'Periodo di Ferie',  
                    'status' => 0,
                    'property' => [
                        'from' => '',
                        'to' => '',
                    ]
                ],
                [
                    'name' => 'Orari di attività',
                    'property' => [],
                ],
                [
                    'name' => 'Posizione',
                    'property' => [],
                ],
                [
                    'name' => 'Contatti',
                    'property' => [],
                ],
                [
                    'name' => 'Possibilità di consegna a domicilio - non attivo',  
                    'property' => []
                ],
            ];
        }

        foreach ($settings as $s) {
            $string = json_encode($s['property'], true);  
            $s['property'] = $string;
            dump( $s['name']);
            // Creazione della voce di impostazione
            Setting::create($s);
        }
    }
}
