<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Legacy URL -> new URL, served as 301 by middleware.
     * This is what keeps the site's search ranking through the cutover.
     *
     * Joomla ran with sef=true, sef_rewrite=false, so public URLs looked like
     * /index.php/courses/french-language as well as the raw component form
     * /index.php?option=com_content&view=article&id=17.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 500);
            $table->string('source_hash', 64)->unique(); // sha256 of normalized source
            $table->string('target', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('published')->default(true);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
