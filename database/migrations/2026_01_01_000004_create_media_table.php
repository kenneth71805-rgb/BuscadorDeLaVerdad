<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->string('path');                       // private directory holding thumb/medium/large/original .jpg
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('bytes');             // size of the stored original variant
            $table->string('alt', 300);
            $table->string('caption', 500)->nullable();
            $table->string('credit', 150)->nullable();
            $table->boolean('is_public')->default(false); // approved for public release (Admin / Editor-in-Chief only)
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['uploader_id', 'created_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('lead_media_id')->nullable()->constrained('media')->nullOnDelete();
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', fn (Blueprint $t) => $t->dropConstrainedForeignId('photo_media_id'));
        Schema::table('articles', fn (Blueprint $t) => $t->dropConstrainedForeignId('lead_media_id'));
        Schema::dropIfExists('media');
    }
};
