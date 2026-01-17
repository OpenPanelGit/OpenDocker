<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCoinsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('coins', 12, 4)->default(0);
            $table->timestamp('last_afk_gain')->nullable();
            $table->integer('bought_cpu')->default(0);
            $table->integer('bought_memory')->default(0);
            $table->integer('bought_disk')->default(0);
            $table->integer('bought_slots')->default(0);
            $table->integer('bought_databases')->default(0);
            $table->integer('bought_backups')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['coins', 'last_afk_gain']);
        });
    }
}
