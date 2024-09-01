<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectLeadGensTable extends Migration
{
    public function up()
    {
        Schema::create('project_lead_gens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('project_id');
            $table->uuid('user_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_lead_gens');
    }
}
