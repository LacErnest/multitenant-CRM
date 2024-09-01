<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmployeeTableAddSocialLinksAndStartingDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('working_hours');
            $table->string('linked_in_profile')->nullable()->after('started_at');
            $table->string('facebook_profile')->nullable()->after('linked_in_profile');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('started_at');
            $table->dropColumn('linked_in_profile');
            $table->dropColumn('facebook_profile');
        });
    }
}
