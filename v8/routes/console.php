<?php
// routes/console.php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
| Этот файл используется для определения консольных команд на основе Closure.
| Каждая Closure привязана к экземпляру команды, что позволяет простой
| подход к построению каждой консольной команды.
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Кастомные команды для проекта Стужа
Artisan::command('stuzha:sync-marketplaces', function () {
    $this->info('Синхронизация с маркетплейсами...');
    app(App\Http\Controllers\MarketplaceController::class)->sync();
    $this->info('Синхронизация завершена!');
})->purpose('Синхронизация товаров с маркетплейсами');

Artisan::command('stuzha:clear-logs', function () {
    $this->info('Очистка старых логов...');
    $files = glob(storage_path('logs/*.log'));
    foreach ($files as $file) {
        if (filemtime($file) < time() - (7 * 24 * 60 * 60)) { // старше 7 дней
            unlink($file);
        }
    }
    $this->info('Логи очищены!');
})->purpose('Очистка старых log файлов');
