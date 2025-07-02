<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Wallets\Enums\LedgerModeType;

class AddModeFieldToWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->enum('mode', LedgerModeType::values())
                ->default(LedgerModeType::SINGLE_USER->value)
                ->comment('帳本模式：single-單人模式, multi-多人模式, couple-情侶模式')
                ->after('properties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
}
