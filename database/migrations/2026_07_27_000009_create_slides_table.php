<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the SP Page Builder carousel addon (and the dead DJ-ImageSlider
     * extension). Grouped so additional sliders can be added without a deploy.
     */
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->default('home')->index();

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('caption')->nullable();

            $table->string('image');                 // disk-relative on `public`
            $table->string('image_alt')->nullable();
            $table->boolean('ai_alt_generated')->default(false);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->string('target', 10)->default('_self');

            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['group', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
