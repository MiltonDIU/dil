<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The lesson breakdown of a course — "Weeks 1–2: Introduction & Basics" style
 * curriculum sections shown as an accordion on the course page.
 *
 * No legacy source exists (the Joomla site stored the whole curriculum as one
 * HTML blob in the article body), so rows are born in the admin. The legacy
 * pair is still carried for consistency with every other content table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('duration_label')->nullable(); // e.g. "Weeks 1–2", "Lesson 3"
            $table->longText('body')->nullable();         // topics covered, rendered as .content-html

            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['course_id', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
