<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('bio')->nullable();
            $table->string('link_url')->nullable();
            $table->boolean('is_public')->default(false); // nothing is public until an Admin approves it
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['is_public', 'archived_at', 'sort_order']);
        });

        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('type', 30);
            $table->text('message');
            $table->string('status', 20)->default('new')->index(); // new | read | resolved
            $table->string('ip_hash', 64)->nullable();            // keyed hash, never the raw IP
            $table->timestamp('closed_at')->nullable()->index();   // retention clock (1 week after closure)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('settings');
    }
};
