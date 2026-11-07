<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * News items — Joomla `a0cbr_content` rows in the "News" category.
     * Both legacy rows are trashed, so this ships empty but fully functional.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('legacy_category_id')->nullable();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_raw')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();

            $table->boolean('published')->default(false);
            $table->boolean('featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('hits')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('ai_meta_generated')->default(false);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
