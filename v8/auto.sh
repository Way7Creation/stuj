#!/bin/bash

# Скрипт инициализации проекта "Стужа"
# Создает необходимую структуру каталогов согласно документации

echo "🚀 Инициализация проекта Стужа..."

# Создание основных директорий Laravel
echo "📁 Создание структуры Laravel..."
mkdir -p app/{Models,Http/{Controllers,Middleware},Services,Console}
mkdir -p bootstrap/cache
mkdir -p config
mkdir -p database/{migrations,factories,seeders}
mkdir -p public/{images,storage}
mkdir -p resources/{views,lang}
mkdir -p routes
mkdir -p storage/{app/{public,images/{products,temp}},framework/{cache,sessions,testing,views},logs}
mkdir -p tests/{Feature,Unit}

# Создание структуры Vue.js
echo "📁 Создание структуры Vue.js..."
mkdir -p client/src/{components,pages,assets/{css,images},composables,services,utils}
mkdir -p client/public

# Создание директории для cron задач
echo "📁 Создание директории cron..."
mkdir -p cron

# Установка прав доступа
echo "🔐 Установка прав доступа..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 .env.example 2>/dev/null || true

# Создание пустых файлов роутов
echo "📄 Создание файлов роутов..."
touch routes/web.php
touch routes/api.php
touch routes/console.php
touch routes/channels.php

# Создание базовых файлов стилей
echo "🎨 Создание файлов стилей..."
cat > client/src/assets/css/app.css << 'EOF'
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Кастомные стили для проекта Стужа */
@layer base {
    body {
        @apply bg-stuzha-bg text-stuzha-text;
    }
}

@layer components {
    /* Стили для эффекта матрёшки */
    .matryoshka-container {
        @apply relative w-full h-96 overflow-hidden rounded-lg;
    }
    
    .matryoshka-container .layer {
        @apply absolute inset-0 w-full h-full object-cover transition-transform duration-500;
    }
    
    .matryoshka-container .inside-content {
        @apply absolute bottom-0 left-0 right-0 bg-black/80 p-4 transform translate-y-full transition-transform duration-500;
    }
    
    .matryoshka-container.active .inside-content {
        @apply translate-y-0;
    }
}

@layer utilities {
    /* Утилиты для анимаций */
    .animation-paused {
        animation-play-state: paused;
    }
    
    .animation-running {
        animation-play-state: running;
    }
}
EOF

# Создание файла postcss.config.js
echo "📄 Создание PostCSS конфигурации..."
cat > postcss.config.js << 'EOF'
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
EOF

# Создание .editorconfig
echo "📄 Создание .editorconfig..."
cat > .editorconfig << 'EOF'
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
indent_style = space
indent_size = 4
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml,json,js,vue}]
indent_size = 2

[docker-compose.yml]
indent_size = 4
EOF

# Создание заглушки для внешней конфигурации
echo "⚙️ Создание примечания о внешней конфигурации..."
cat > EXTERNAL_CONFIG.md << 'EOF'
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
EOF

echo "✅ Структура проекта создана!"
echo ""
echo "Следующие шаги:"
echo "1. Установите зависимости: composer install && npm install"
echo "2. Настройте внешнюю конфигурацию согласно EXTERNAL_CONFIG.md"
echo "3. Запустите миграции: php artisan migrate"
echo "4. Запустите сборку фронтенда: npm run dev"
echo ""
echo "🎯 Готово к переходу на Этап 2: Миграции и модели"