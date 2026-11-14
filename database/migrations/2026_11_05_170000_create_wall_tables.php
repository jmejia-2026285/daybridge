<?php

use App\Enums\WallReactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wall_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 1000);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['is_pinned', 'created_at']);
        });

        Schema::create('wall_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wall_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default(WallReactionType::Like->value);
            $table->timestamps();

            $table->unique(['wall_post_id', 'user_id']);
        });

        Schema::create('wall_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wall_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->timestamps();

            $table->index(['wall_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_comments');
        Schema::dropIfExists('wall_reactions');
        Schema::dropIfExists('wall_posts');
    }
};
