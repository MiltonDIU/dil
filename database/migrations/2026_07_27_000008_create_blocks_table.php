<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Homepage sections, replacing SP Page Builder.
     *
     * The legacy homepage decomposes into only 7 addon types, so this is a
     * deterministic mapping — no AI. `raw_layout` keeps the original SPPB JSON
     * for that block forever, so nothing is ever unrecoverable.
     */
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->default('home')->index();

            /*
             * type: carousel | rich_text | feature_cards | video | link_list
             *     | map | html | cta
             */
            $table->string('type', 30);

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable();   // cleaned HTML where relevant
            $table->json('data')->nullable();          // typed payload per block type
            $table->string('image')->nullable();       // disk-relative on `public`
            $table->string('image_alt')->nullable();

            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();

            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('full_width')->default(false);
            $table->string('background', 20)->nullable(); // white|light|dark|brand

            $table->longText('raw_layout')->nullable(); // original SPPB JSON, never overwritten

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['group', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
