<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Static content pages — About, Mission & Vision, Director Message,
     * Contact, Welcome Message, etc. Sourced from Joomla `a0cbr_content`
     * rows in the "Main" category that are not course pages.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();        // cleaned, asset paths rewritten
            $table->longText('body_raw')->nullable();    // exactly as it came out of Joomla
            $table->string('featured_image')->nullable(); // disk-relative on `public`
            $table->string('featured_image_alt')->nullable();

            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->unsignedBigInteger('hits')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('ai_meta_generated')->default(false);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['published', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
