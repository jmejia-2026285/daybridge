<?php

use App\Enums\ServiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('type', 60);
            $table->string('brand', 60);
            $table->string('model', 80);
            $table->string('serial', 80)->nullable();
            $table->string('client_name', 100);
            $table->string('client_phone', 20);
            $table->string('client_email', 120)->nullable();
            $table->string('issue', 255);
            $table->text('diagnosis')->nullable();
            $table->string('accessories', 500)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('status', 20)->default(ServiceStatus::Pending->value)->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->useCurrent();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
