<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The site's actual subject: language courses.
     *
     * In Joomla these were plain articles whose fees, duration and structure
     * were buried in inconsistent prose ("Fees: Tk. 12000/- (Regular), Tk.
     * 8,000/- (Daffodil Family)"). Those values are promoted to typed columns
     * here, extracted by Gemini — with `body_raw` preserved untouched so the
     * page still renders correctly if AI is skipped or wrong.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            // --- taxonomy (AI-normalized, raw kept alongside) ---
            $table->string('language')->nullable();          // canonical: German, French…
            $table->string('language_raw')->nullable();
            $table->json('cefr_levels')->nullable();         // ["A1","A2"]
            $table->string('cefr_levels_raw')->nullable();

            // --- structure ---
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->unsignedSmallInteger('classes_per_week')->nullable();
            $table->unsignedSmallInteger('total_classes')->nullable();
            $table->unsignedSmallInteger('class_duration_minutes')->nullable();

            // --- fees (three tiers seen across every course article) ---
            $table->decimal('fee_regular', 10, 2)->nullable();
            $table->decimal('fee_daffodil_family', 10, 2)->nullable();
            $table->decimal('fee_outside', 10, 2)->nullable();
            $table->string('currency', 8)->default('BDT');
            $table->string('fee_note')->nullable();

            // --- content ---
            $table->text('summary')->nullable();
            $table->longText('curriculum_html')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_raw')->nullable();   // never overwritten
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('icon')->nullable();         // heroicon / fa name

            $table->boolean('published')->default(false);
            $table->boolean('featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->unsignedBigInteger('hits')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('ai_meta_generated')->default(false);

            // --- AI provenance ---
            $table->boolean('ai_normalized')->default(false);
            $table->timestamp('ai_extracted_at')->nullable();
            $table->timestamp('ai_reviewed_at')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['published', 'ordering']);
            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
