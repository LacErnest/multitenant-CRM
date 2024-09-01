<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('xero_id_token', 2056)->nullable()->after('xero_refresh_token');
            $table->string('xero_oauth2_state', 2056)->nullable()->after('xero_id_token');
            $table->timestamp('xero_expires')->nullable()->after('xero_oauth2_state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
