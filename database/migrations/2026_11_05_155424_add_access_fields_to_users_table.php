<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default(UserRole::User->value)->after('password')->index();
            $table->string('it_desk_role', 20)->nullable()->after('role')->index();
            $table->boolean('is_active')->default(true)->after('it_desk_role')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'it_desk_role', 'is_active']);
        });
    }
};
