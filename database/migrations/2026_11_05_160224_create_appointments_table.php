<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('description', 500)->nullable();
            $table->date('scheduled_date')->index();
            $table->time('scheduled_time');
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index(['technician_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
