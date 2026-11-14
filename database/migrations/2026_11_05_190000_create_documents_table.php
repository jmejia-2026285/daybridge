<?php

use App\Enums\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->string('description', 500)->nullable();
            $table->string('category', 30)->default(DocumentCategory::Other->value)->index();
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->index(['area_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
