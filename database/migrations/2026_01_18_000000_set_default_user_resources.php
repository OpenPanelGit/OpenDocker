<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SetDefaultUserResources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Set default resources for existing users who have 0 resources
        // This is a one-time fix for users created before the store system
        DB::table('users')
            ->where('bought_cpu', 0)
            ->where('bought_memory', 0)
            ->where('bought_disk', 0)
            ->where('bought_slots', 0)
            ->update([
                'bought_cpu' => 100,
                'bought_memory' => 2048,
                'bought_disk' => 4096,
                'bought_slots' => 1,
                'bought_databases' => 2,
                'bought_backups' => 2,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No rollback needed - this is a data fix
    }
}
