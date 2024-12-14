<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class DeletePending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct()
    {
        //
    }

    public function handle()
    {
        DB::table('order_product')
        ->whereIn('order_id', function ($query) {
            $query->select('id')
                ->from('orders')
                ->where('status', 4);
        })
        ->delete();
        
        DB::table('orders')
            ->where('status', 4)
            ->where('created_at', '<=', Carbon::now()->subMinutes(20))
            ->delete();
         
    }
}
