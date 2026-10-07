<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected function schedule(Schedule $schedule)
    {
        // Cron cPanel unico:  * * * * * php artisan schedule:run
        $schedule->command('wa:retry-deliveries')->everyMinute()->withoutOverlapping();
    }

   
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
