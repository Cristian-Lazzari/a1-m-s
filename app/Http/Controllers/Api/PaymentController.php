<?php

namespace App\Http\Controllers\Api;

use Stripe\Stripe;
use App\Models\Order;
use App\Models\Setting;
use Stripe\PaymentIntent;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{        
    public function checkout($cart, $id, $delivery) 
    {
       
        $final_destination = config('configurazione.domain') . '/success-pay'; 
        $final_destination_error = config('configurazione.domain') . '/error-pay'; 
        $stripeSecretKey = config('configurazione.STRIPE_SECRET'); 

        $stripe = new \Stripe\StripeClient($stripeSecretKey);
 
        $line_items = [];
        foreach ($cart as $orderProduct) {
            //return [$orderProduct->quantity, $orderProduct->price];
            $line_items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $orderProduct->name,
                    ],
                    'unit_amount' => $orderProduct->price, // Prezzo totale del prodotto con gli extra
                ],
                'quantity' => $orderProduct->pivot->quantity,
            ];

            // Calcola i prezzi degli ingredienti aggiunti
            $added_ingredients = json_decode($orderProduct->pivot->add, true); // Decodifica la stringa add
            $option_ingredients = json_decode($orderProduct->pivot->option, true); // Decodifica la stringa option
            $removed_ingredients = json_decode($orderProduct->pivot->remove, true); // Decodifica la stringa remove



            // Aggiungi prezzi ingredienti 'add'
            if ($added_ingredients) {
                foreach ($added_ingredients as $ingredient_name) {
                    $ingredient = Ingredient::where('name', $ingredient_name)->first();
                    if ($ingredient) {
                        $line_items[] = [
                            'price_data' => [
                                'currency' => 'eur',
                                'product_data' => [
                                    'name' => $ingredient_name . '(EXTRA)',
                                ],
                                'unit_amount' => $ingredient->price, 
                            ],
                            'quantity' => $orderProduct->pivot->quantity,
                        ];
                        
                    }
                }
            }

            // Aggiungi prezzi ingredienti 'option'
            if ($option_ingredients) {      
                foreach ($option_ingredients as $option_name) {
                    $option = Ingredient::where('name', $option_name)->first();
                    if ($option) {
                        $line_items[] = [
                            'price_data' => [
                                'currency' => 'eur',
                                'product_data' => [
                                    'name' => $option_name . '(EXTRA)',
                                ],
                                'unit_amount' => $option->price, 
                            ],
                            'quantity' => $orderProduct->pivot->quantity,
                        ];
                        

                    }
                }
            }
            

            if ($removed_ingredients) {
                foreach ($removed_ingredients as $option_name) {
                    $option = Ingredient::where('name', $option_name)->first();
                    if ($option) {
                        $line_items[] = [
                            'price_data' => [
                                'currency' => 'eur',
                                'product_data' => [
                                    'name' => $option_name . '(RIMOSSO)',
                                ],
                                'unit_amount' => 0, 
                            ],
                            'quantity' => $orderProduct->pivot->quantity,
                        ];

                    }
                }
            }
        }
        if($delivery){
            $setting = Setting::where('name', 'Possibilità di consegna a domicilio')->first();
            $shipping_cost = json_decode($setting->property, 1);
            // Aggiungi costo di spedizione come item separato se non è 0
            if($shipping_cost['delivery_cost'] > 0) {
                $line_items[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Spese di spedizione',
                        ],
                        'unit_amount' => $shipping_cost['delivery_cost'], // Costo di spedizione in centesimi
                    ],
                    'quantity' => 1,
                ];
            }
        }

        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => $line_items,
            'mode' => 'payment',
            'metadata' => [
                'order_id' => $id,
            ],
            'success_url' => $final_destination,
            'cancel_url' => $final_destination_error,
        ]);

        
        return $checkout_session->url;

    }

}