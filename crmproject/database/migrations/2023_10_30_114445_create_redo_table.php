<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('users');
            $table->string('customer_name');
            $table->string('contact_no');
            $table->string('reg_no');
            $table->string('technician');
            $table->string('charges');
            $table->string('old_device');
            $table->string('new_device');
            $table->string('eng_no');
            $table->string('chasis_no');
            $table->string('install_loc');
            $table->string('install_date');
            $table->string('remarks');
            $table->string('sales_person');
            $table->string('harness_change');
            $table->string('backupbattery_change');

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
        Schema::dropIfExists('redo');
    }
};
