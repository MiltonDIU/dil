<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue of migrated files, so the admin has a media library view
        // and Phase 8 can report dead references with numbers.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path');                 // disk-relative, e.g. media/images/home/dil.png
            $table->string('disk', 20)->default('public');
            $table->string('filename');
            $table->string('extension', 12)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('folder')->nullable()->index();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->string('legacy_path')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });

        /*
         * Cache of every AI decision, so that:
         *   - re-runs are free and deterministic,
         *   - a human can review and correct a decision in Filament,
         *   - corrections survive the next re-run (we look here before calling
         *     the API, and never overwrite a reviewed row).
         */
        Schema::create('ai_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60)->index();      // course_extract, seo_meta, image_alt…
            $table->string('input_hash', 64);
            $table->longText('input');
            $table->json('output_json')->nullable();
            $table->string('model', 60)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['type', 'input_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_mappings');
        Schema::dropIfExists('media');
    }
};
