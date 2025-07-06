<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/TelegramController.php
| Описание: ПОЛНЫЙ контроллер Telegram бота для заказов
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    /**
     * Telegram Bot Token (из .env)
     */
    private function getBotToken(): string
    {
        return env('TELEGRAM_BOT_TOKEN', '');
    }

    /**
     * Chat ID администратора (из .env)
     */
    private function getAdminChatId(): string
    {
        return env('TELEGRAM_ADMIN_CHAT_ID', '');
    }

    /**
     * Обработка webhook от Telegram
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $update = $request->all();
            
            Log::info('Telegram webhook received', $update);

            // Проверяем, что это сообщение
            if (!isset($update['message'])) {
                return response()->json(['status' => 'ok']);
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $userId = $message['from']['id'];
            $userName = $message['from']['first_name'] ?? 'Пользователь';

            // Обрабатываем команды
            if (str_starts_with($text, '/start')) {
                $this->handleStartCommand($chatId, $text, $userName);
            } elseif (str_starts_with($text, '/order_')) {
                $this->handleOrderCommand($chatId, $text, $userName, $userId);
            } elseif (str_starts_with($text, '/catalog')) {
                $this->handleCatalogCommand($chatId, $userName);
            } elseif (str_starts_with($text, '/help')) {
                $this->handleHelpCommand($chatId, $userName);
            } else {
                $this->handleUnknownCommand($chatId, $userName);
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Обработка команды /start
     */
    private function handleStartCommand(string $chatId, string $text, string $userName): void
    {
        // Проверяем, есть ли параметр заказа
        if (preg_match('/\/start order_(\d+)/', $text, $matches)) {
            $productId = $matches[1];
            $this->handleOrderCommand($chatId, '/order_' . $productId, $userName, null);
            return;
        }

        $message = "👋 Привет, {$userName}!\n\n";
        $message .= "Добро пожаловать в магазин украшений **Стужа**!\n\n";
        $message .= "🔹 Мы создаем уникальные украшения с эффектом \"матрёшки\"\n";
        $message .= "🔹 Каждое изделие - это произведение искусства\n";
        $message .= "🔹 Персональный подбор камней по дате рождения\n\n";
        $message .= "**Доступные команды:**\n";
        $message .= "/catalog - Посмотреть каталог\n";
        $message .= "/help - Получить помощь\n\n";
        $message .= "🌐 Наш сайт: " . env('APP_URL', 'https://stuj.ru');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Обработка команды заказа
     */
    private function handleOrderCommand(string $chatId, string $text, string $userName, ?int $userId): void
    {
        // Извлекаем ID товара из команды
        if (preg_match('/\/order_(\d+)/', $text, $matches)) {
            $productId = $matches[1];
            
            // Находим товар
            $product = Product::with(['theme', 'categories'])->find($productId);
            
            if (!$product) {
                $this->sendMessage($chatId, "❌ Товар не найден. Возможно, он был удален из каталога.");
                return;
            }

            // Отправляем информацию о товаре пользователю
            $this->sendProductInfo($chatId, $product, $userName);
            
            // Уведомляем администратора о новом заказе
            $this->notifyAdminAboutOrder($product, $userName, $userId, $chatId);
            
        } else {
            $this->sendMessage($chatId, "❌ Некорректная команда заказа.");
        }
    }

    /**
     * Отправка информации о товаре
     */
    private function sendProductInfo(string $chatId, Product $product, string $userName): void
    {
        $message = "🛍️ **Информация о товаре**\n\n";
        $message .= "📝 **Название:** {$product->name}\n";
        $message .= "💰 **Цена:** " . number_format($product->price, 0, ',', ' ') . " ₽\n";
        
        if ($product->theme) {
            $message .= "🎨 **Тема:** {$product->theme->name}\n";
        }
        
        if ($product->categories->isNotEmpty()) {
            $categories = $product->categories->pluck('name')->implode(', ');
            $message .= "📂 **Категории:** {$categories}\n";
        }
        
        $message .= "\n📋 **Описание:**\n{$product->description}\n\n";
        
        if ($product->use_matryoshka) {
            $message .= "✨ *Этот товар имеет уникальный эффект \"матрёшки\"*\n\n";
        }
        
        $message .= "📞 **Для оформления заказа:** наш менеджер свяжется с вами в ближайшее время!\n\n";
        $message .= "🌐 Посмотреть на сайте: " . env('APP_URL') . "/product/{$product->slug}";

        // Отправляем изображение товара если есть
        if ($product->gallery_images && count($product->gallery_images) > 0) {
            $imageUrl = env('APP_URL') . '/storage/images/' . $product->gallery_images[0];
            $this->sendPhoto($chatId, $imageUrl, $message);
        } else {
            $this->sendMessage($chatId, $message);
        }
    }

    /**
     * Уведомление администратора о заказе
     */
    private function notifyAdminAboutOrder(Product $product, string $userName, ?int $userId, string $chatId): void
    {
        $adminChatId = $this->getAdminChatId();
        
        if (empty($adminChatId)) {
            Log::warning('Admin chat ID not configured');
            return;
        }

        $message = "🔔 **НОВЫЙ ЗАКАЗ!**\n\n";
        $message .= "👤 **Клиент:** {$userName}\n";
        if ($userId) {
            $message .= "🆔 **User ID:** {$userId}\n";
        }
        $message .= "💬 **Chat ID:** {$chatId}\n\n";
        $message .= "🛍️ **Товар:** {$product->name}\n";
        $message .= "💰 **Цена:** " . number_format($product->price, 0, ',', ' ') . " ₽\n";
        $message .= "🕐 **Время:** " . now()->format('d.m.Y H:i:s') . "\n\n";
        $message .= "🌐 **Ссылка на товар:** " . env('APP_URL') . "/product/{$product->slug}";

        $this->sendMessage($adminChatId, $message);
    }

    /**
     * Обработка команды каталога
     */
    private function handleCatalogCommand(string $chatId, string $userName): void
    {
        $message = "📁 **Каталог украшений Стужа**\n\n";
        $message .= "🌐 Полный каталог доступен на нашем сайте:\n";
        $message .= env('APP_URL', 'https://stuj.ru') . "/catalog\n\n";
        $message .= "🔹 Кольца с натуральными камнями\n";
        $message .= "🔹 Серьги с эффектом \"матрёшки\"\n";
        $message .= "🔹 Браслеты уникального дизайна\n";
        $message .= "🔹 Подвески и кулоны\n\n";
        $message .= "✨ Каждое украшение создается вручную нашими мастерами!\n\n";
        $message .= "🎯 Пройдите астрологический квиз для персонального подбора:\n";
        $message .= env('APP_URL', 'https://stuj.ru') . "/quiz";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Обработка команды помощи
     */
    private function handleHelpCommand(string $chatId, string $userName): void
    {
        $message = "❓ **Помощь по боту**\n\n";
        $message .= "**Доступные команды:**\n";
        $message .= "/start - Главное меню\n";
        $message .= "/catalog - Посмотреть каталог\n";
        $message .= "/help - Эта справка\n\n";
        $message .= "**Как сделать заказ:**\n";
        $message .= "1️⃣ Найдите товар на сайте\n";
        $message .= "2️⃣ Нажмите кнопку \"Заказать в Telegram\"\n";
        $message .= "3️⃣ Дождитесь сообщения от менеджера\n\n";
        $message .= "📞 **Контакты:**\n";
        $message .= "🌐 Сайт: " . env('APP_URL', 'https://stuj.ru') . "\n";
        $message .= "📧 Email: " . env('CONTACT_EMAIL', 'info@stuj.ru') . "\n";
        $message .= "📱 Телефон: " . env('CONTACT_PHONE', '+7 (xxx) xxx-xx-xx');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Обработка неизвестной команды
     */
    private function handleUnknownCommand(string $chatId, string $userName): void
    {
        $message = "❓ Извините, я не понимаю эту команду.\n\n";
        $message .= "Используйте /help для получения списка доступных команд.\n\n";
        $message .= "Или перейдите на наш сайт: " . env('APP_URL', 'https://stuj.ru');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Отправка текстового сообщения
     */
    private function sendMessage(string $chatId, string $text): void
    {
        $botToken = $this->getBotToken();
        
        if (empty($botToken)) {
            Log::error('Telegram bot token not configured');
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];

        try {
            $response = Http::post($url, $data);
            
            if (!$response->successful()) {
                Log::error('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Telegram API error: ' . $e->getMessage());
        }
    }

    /**
     * Отправка фотографии с подписью
     */
    private function sendPhoto(string $chatId, string $photoUrl, string $caption): void
    {
        $botToken = $this->getBotToken();
        
        if (empty($botToken)) {
            Log::error('Telegram bot token not configured');
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        
        $data = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'Markdown'
        ];

        try {
            $response = Http::post($url, $data);
            
            if (!$response->successful()) {
                Log::error('Failed to send Telegram photo', [
                    'chat_id' => $chatId,
                    'photo_url' => $photoUrl,
                    'response' => $response->body()
                ]);
                
                // Если не удалось отправить фото, отправляем текст
                $this->sendMessage($chatId, $caption);
            }
        } catch (\Exception $e) {
            Log::error('Telegram API photo error: ' . $e->getMessage());
            // Отправляем текст как запасной вариант
            $this->sendMessage($chatId, $caption);
        }
    }

    /**
     * Установка webhook для бота (вызывается вручную)
     * POST /api/admin/telegram/set-webhook
     */
    public function setWebhook(Request $request): JsonResponse
    {
        $botToken = $this->getBotToken();
        
        if (empty($botToken)) {
            return response()->json([
                'error' => 'Telegram bot token not configured'
            ], 500);
        }

        $webhookUrl = env('APP_URL') . '/api/telegram/webhook';
        $url = "https://api.telegram.org/bot{$botToken}/setWebhook";
        
        try {
            $response = Http::post($url, [
                'url' => $webhookUrl
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook установлен успешно',
                    'webhook_url' => $webhookUrl,
                    'response' => $response->json()
                ]);
            } else {
                return response()->json([
                    'error' => 'Ошибка установки webhook',
                    'response' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка API Telegram: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение информации о webhook
     * GET /api/admin/telegram/webhook-info
     */
    public function getWebhookInfo(): JsonResponse
    {
        $botToken = $this->getBotToken();
        
        if (empty($botToken)) {
            return response()->json([
                'error' => 'Telegram bot token not configured'
            ], 500);
        }

        $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
        
        try {
            $response = Http::get($url);
            
            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json([
                    'error' => 'Ошибка получения информации о webhook'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка API Telegram: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление webhook
     * DELETE /api/admin/telegram/webhook
     */
    public function deleteWebhook(): JsonResponse
    {
        $botToken = $this->getBotToken();
        
        if (empty($botToken)) {
            return response()->json([
                'error' => 'Telegram bot token not configured'
            ], 500);
        }

        $url = "https://api.telegram.org/bot{$botToken}/deleteWebhook";
        
        try {
            $response = Http::post($url);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook удален успешно'
                ]);
            } else {
                return response()->json([
                    'error' => 'Ошибка удаления webhook'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка API Telegram: ' . $e->getMessage()
            ], 500);
        }
    }
}