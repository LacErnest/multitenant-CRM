<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTypeAndStatusNotNullableForResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE resources CHANGE `type` `type` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;');
        DB::statement('ALTER TABLE resources CHANGE `status` `status` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->nullable()->change();
            $table->unsignedTinyInteger('status')->nullable()->change();
        });
    }
}
