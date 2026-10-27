<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Privacy-conscious readership counter: one number per article per day. No IPs, cookies or visitor ids.
        Schema::create('article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->unsignedInteger('views')->default(0);
            $table->unique(['article_id', 'viewed_on']);
            $table->index('viewed_on');
        });

        Schema::create('faq_entries', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('keywords', 500)->nullable(); // comma-separated words that should trigger this answer
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Questions the helper could not answer, so editors can write a new FAQ. Scrubbed, anonymous, deleted after a week.
        Schema::create('assistant_misses', function (Blueprint $table) {
            $table->id();
            $table->string('question', 200);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_misses');
        Schema::dropIfExists('faq_entries');
        Schema::dropIfExists('article_views');
    }
};
