<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSharedToEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('master')->default(0)->after('manual_input');
            $table->boolean('shadow')->default(0)->after('master');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('master')->default(0)->after('manual_input');
            $table->boolean('shadow')->default(0)->after('master');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('master')->default(0)->after('manual_input');
            $table->boolean('shadow')->default(0)->after('master');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('master', 'shadow');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('master', 'shadow');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('master', 'shadow');
        });
    }
}
