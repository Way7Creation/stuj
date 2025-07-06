<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Сначала выводим информацию о существующих индексах для отладки
        $indexes = DB::select("SHOW INDEX FROM marketplace_maps");
        echo "\nСуществующие индексы в marketplace_maps:\n";
        foreach ($indexes as $index) {
            echo "- {$index->Key_name} on column {$index->Column_name}\n";
        }
        
        // Сохраняем существующие данные
        $existingData = DB::table('marketplace_maps')->get();
        
        // Получаем список всех индексов
        $indexNames = array_map(function($index) {
            return $index->Key_name;
        }, $indexes);
        
        // Удаляем уникальные индексы, которые могут мешать
        Schema::table('marketplace_maps', function (Blueprint $table) use ($indexNames) {
            // Пробуем разные варианты названий индекса
            $possibleIndexNames = [
                'marketplace_maps_marketplace_our_attr_id_unique',
                'marketplace_maps_marketplace_our_id_unique',
                'marketplace_our_attr_id_unique',
                'marketplace_our_id_unique'
            ];
            
            foreach ($possibleIndexNames as $indexName) {
                if (in_array($indexName, $indexNames)) {
                    try {
                        // Сначала пробуем как unique
                        $table->dropUnique($indexName);
                        echo "Удален unique индекс: $indexName\n";
                    } catch (\Exception $e) {
                        try {
                            // Если не получилось, пробуем как обычный индекс
                            $table->dropIndex($indexName);
                            echo "Удален индекс: $indexName\n";
                        } catch (\Exception $e2) {
                            echo "Не удалось удалить индекс: $indexName\n";
                        }
                    }
                }
            }
        });
        
        // Модифицируем таблицу
        Schema::table('marketplace_maps', function (Blueprint $table) {
            // Добавляем новые поля
            if (!Schema::hasColumn('marketplace_maps', 'mapping_type')) {
                $table->enum('mapping_type', ['category', 'attribute'])
                    ->default('attribute')
                    ->after('marketplace')
                    ->comment('Тип маппинга');
                echo "Добавлено поле: mapping_type\n";
            }
            
            // Добавляем поле marketplace_id если его нет
            if (!Schema::hasColumn('marketplace_maps', 'marketplace_id')) {
                $table->string('marketplace_id')
                    ->after('our_id')
                    ->default('')
                    ->comment('ID категории/атрибута на маркетплейсе');
                echo "Добавлено поле: marketplace_id\n";
            }
            
            // Проверяем, нужно ли переименовывать marketplace_attr_name
            if (Schema::hasColumn('marketplace_maps', 'marketplace_attr_name') && 
                !Schema::hasColumn('marketplace_maps', 'marketplace_name')) {
                $table->renameColumn('marketplace_attr_name', 'marketplace_name');
                echo "Переименовано поле: marketplace_attr_name -> marketplace_name\n";
            }
            
            // Добавляем поле для дополнительных данных
            if (!Schema::hasColumn('marketplace_maps', 'additional_data')) {
                $table->json('additional_data')
                    ->nullable()
                    ->comment('Дополнительные данные');
                echo "Добавлено поле: additional_data\n";
            }
        });
        
        // Обновляем существующие записи
        foreach ($existingData as $data) {
            $updateData = [
                'mapping_type' => 'attribute',
            ];
            
            // Заполняем marketplace_id
            if (empty($data->marketplace_id)) {
                // Пробуем разные варианты имен полей
                $marketplaceName = $data->marketplace_name ?? 
                                 $data->marketplace_attr_name ?? 
                                 '';
                $updateData['marketplace_id'] = $marketplaceName;
            }
            
            DB::table('marketplace_maps')
                ->where('id', $data->id)
                ->update($updateData);
        }
        
        echo "Обновлено записей: " . count($existingData) . "\n";
        
        // Убираем default с marketplace_id
        Schema::table('marketplace_maps', function (Blueprint $table) {
            $table->string('marketplace_id')->default(null)->change();
        });
        
        // Добавляем новые индексы
        Schema::table('marketplace_maps', function (Blueprint $table) {
            // Получаем актуальный список индексов
            $currentIndexes = DB::select("SHOW INDEX FROM marketplace_maps");
            $currentIndexNames = array_map(function($index) {
                return $index->Key_name;
            }, $currentIndexes);
            
            if (!in_array('marketplace_mapping_type_index', $currentIndexNames)) {
                $table->index(['marketplace', 'mapping_type'], 'marketplace_mapping_type_index');
                echo "Добавлен индекс: marketplace_mapping_type_index\n";
            }
            
            if (!in_array('marketplace_our_id_index', $currentIndexNames)) {
                $table->index(['marketplace', 'our_id'], 'marketplace_our_id_index');
                echo "Добавлен индекс: marketplace_our_id_index\n";
            }
            
            if (!in_array('unique_mapping', $currentIndexNames)) {
                $table->unique(['marketplace', 'mapping_type', 'our_id'], 'unique_mapping');
                echo "Добавлен уникальный индекс: unique_mapping\n";
            }
        });
        
        echo "\nМиграция успешно выполнена!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем индексы
        Schema::table('marketplace_maps', function (Blueprint $table) {
            $indexes = ['marketplace_mapping_type_index', 'marketplace_our_id_index', 'unique_mapping'];
            
            foreach ($indexes as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Exception $e) {
                    // Игнорируем если индекса нет
                }
            }
        });
        
        // Возвращаем поля обратно
        Schema::table('marketplace_maps', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_maps', 'marketplace_name') && 
                !Schema::hasColumn('marketplace_maps', 'marketplace_attr_name')) {
                $table->renameColumn('marketplace_name', 'marketplace_attr_name');
            }
            
            // Удаляем новые поля
            $columnsToRemove = ['mapping_type', 'marketplace_id', 'additional_data'];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('marketplace_maps', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};