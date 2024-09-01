<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('html_file');
            $table->uuid('design_template_id')->nullable();
            $table->foreign('design_template_id')->references('id')->on('design_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->string('html_file', 100)->nullable();
        });
    }
}
