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
        Schema::create('workers_checkout', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emp_id');
            $table->foreign('emp_id')->references('emp_id')->on('workers');
            $table->unsignedBigInteger('login_id');
            $table->foreign('login_id')->references('id')->on('workers_login');
            $table->string('Emp_name')->nullable();
            $table->string('logout_id')->nullable();
            $table->string('password')->nullable();
            $table->string('logout_day')->nullable();
            $table->string('forget_password_status')->nullable();
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
        Schema::dropIfExists('workers_checkout');
    }
};
