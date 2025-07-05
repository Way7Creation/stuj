<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\MarketplaceController;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Синхронизация маркетплейсов каждый час
        $schedule->call(function () {
            app(MarketplaceController::class)->sync();
        })->hourly()->name('marketplace-sync')->withoutOverlapping();
        
        // Очистка старых логов раз в неделю
        $schedule->command('log:clear')->weekly();
        
        // Резервное копирование БД каждый день в 3:00
        $schedule->command('backup:run')->dailyAt('03:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}