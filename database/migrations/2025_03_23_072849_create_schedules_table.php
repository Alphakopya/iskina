<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id'); // Match employees.employee_id type
            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('work_days');
            $table->date('day_off');
            $table->json('holidays')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedules');
    }
};
