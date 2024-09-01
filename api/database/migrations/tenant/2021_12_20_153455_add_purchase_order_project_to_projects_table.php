<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseOrderProjectToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('purchase_order_project')->default(0)->after('name');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('purchase_order_project');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('set null');
        });
    }
}
