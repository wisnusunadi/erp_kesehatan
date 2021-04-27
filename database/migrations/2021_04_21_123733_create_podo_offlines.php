<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePodoOfflines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('podo_offlines', function (Blueprint $table) {
            $table->id();
            $table->integer('offline_id');
            $table->string('po');
            $table->date('tglpo')->nullable();
            $table->string('do')->nullable();
            $table->date('tgldo')->nullable();
            $table->string('file')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('podo_offlines');
    }
}
