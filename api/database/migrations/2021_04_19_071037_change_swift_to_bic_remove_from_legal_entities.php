<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSwiftToBicRemoveFromLegalEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->renameColumn('swift', 'iban');
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->dropColumn(['swift', 'bic']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->renameColumn('iban', 'swift');
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->string('swift')->after('address_id');
            $table->string('bic')->after('swift');
        });
    }
}
