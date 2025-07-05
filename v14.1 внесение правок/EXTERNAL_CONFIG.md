# Внешняя конфигурация

Конфигурационный файл `.env` должен быть размещен в `/etc/stuj/.env` для безопасности.

## Настройка:

1. Создайте директорию (от root):
   ```bash
   sudo mkdir -p /etc/stuj
   ```

2. Скопируйте пример конфигурации:
   ```bash
   sudo cp .env.example /etc/stuj/.env
   ```

3. Установите права:
   ```bash
   sudo chown www-data:www-data /etc/stuj/.env
   sudo chmod 600 /etc/stuj/.env
   ```

4. Отредактируйте файл:
   ```bash
   sudo nano /etc/stuj/.env
   ```

Файл `bootstrap/app.php` уже настроен для загрузки конфигурации из этого расположения.
