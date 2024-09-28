<?php
namespace Database\Seeders;

use App\Models\Categories\Entities\CategoryEntity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
class InitializationCategorySeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $insert_data = [
            [
                'name' => '早餐',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '午餐',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '晚餐',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '飲品',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '點心',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '交通',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '購物',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '日用品',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '房租',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '娛樂',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '禮物',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '醫療',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '社交',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '學習',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '其他',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Schema::disableForeignKeyConstraints();
        CategoryEntity::truncate();
        CategoryEntity::insert($insert_data);
        Schema::enableForeignKeyConstraints();

        echo self::class . ' Complete' . PHP_EOL . PHP_EOL;
        echo self::class . ' Complete' . PHP_EOL . PHP_EOL;
        echo 'php artisan db:seed --class=InitializationCategorySeeder' . PHP_EOL;
    }
}