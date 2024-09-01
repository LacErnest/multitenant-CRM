<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEarnoutFieldsToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedTinyInteger('earnout_years')->default(3)->after('acquisition_date');
            $table->decimal('earnout_bonus', 5, 2)->default(9)->after('earnout_years');
            $table->decimal('gm_bonus', 5, 2)->default(18)->after('earnout_bonus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['earnout_years', 'earnout_bonus', 'gm_bonus']);
        });
    }
}
