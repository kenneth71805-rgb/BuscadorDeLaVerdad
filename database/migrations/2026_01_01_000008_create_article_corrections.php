<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            // The proposed text. The live article is untouched until a correction is approved.
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('body');
            $table->text('reason');                         // internal: what is wrong and why it is being changed
            $table->string('public_note', 500)->nullable(); // optional public correction notice
            $table->string('status', 20)->default('pending'); // pending | applied | declined
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_reason')->nullable();
            // The text that was replaced, kept for the editorial record.
            $table->string('previous_title')->nullable();
            $table->string('previous_subtitle')->nullable();
            $table->longText('previous_body')->nullable();
            $table->timestamps();
            $table->index(['article_id', 'status']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('corrected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->dropColumn('corrected_at'));
        Schema::dropIfExists('article_revisions');
    }
};
