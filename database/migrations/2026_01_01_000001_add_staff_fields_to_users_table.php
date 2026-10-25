<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->index();      // null = no staff access
            $table->boolean('is_active')->default(true);
            $table->string('position')->nullable();
            $table->boolean('can_override')->default(false);  // Admin editorial override
            $table->boolean('can_publish')->default(false);   // Head of Category publish grant
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'position', 'can_override', 'can_publish']);
        });
    }
};
