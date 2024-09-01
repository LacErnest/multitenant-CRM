<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterShadowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_shadows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('master_id');
            $table->uuid('shadow_id');
            $table->uuid('master_company_id')->nullable();
            $table->uuid('shadow_company_id')->nullable();
            $table->timestamps();

            $table->foreign('master_company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('shadow_company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_shadows');
    }
}
