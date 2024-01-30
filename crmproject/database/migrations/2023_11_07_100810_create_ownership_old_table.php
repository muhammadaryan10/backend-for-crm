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
        Schema::create('ownership_old', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('users');
            $table->string('old_customer');
            $table->string('old_father');
            $table->string('old_telephone');
            $table->string('old_address');
            $table->string('old_mobileno_1');
            $table->string('old_mobileno_2');
            $table->string('old_mobileno_3');
            $table->string('old_mobileno_4');
            $table->string('old_ntn');
            $table->string('old_cnic');
            $table->string('old_primaryname');
            $table->string('old_primary_con1');
            $table->string('old_primary_con2');
            $table->string('old_primary_cnic');
            $table->string('old_seconadry_name');
            $table->string('old_relationship');
            $table->string('old_reg_no');
            $table->string('old_chasis_no');
            $table->string('old_engine_no');
            $table->string('old_make');
            $table->string('old_color');
            $table->string('old_tracker_charges');
            $table->string('old_date');
            $table->string('old_discount');
            $table->string('old_install_loc');
            $table->string('old_conatct_person');
            $table->string('old_remarks')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('ownership_transfer');
    }
};
