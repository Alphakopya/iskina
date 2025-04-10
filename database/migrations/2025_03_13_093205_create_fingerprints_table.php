<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_id')->unique();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('branch');
            $table->tinyInteger('fingerprint_select');
            $table->tinyInteger('del_fingerid');
            $table->tinyInteger('add_fingerid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
