<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();          // mainmenu, footer…
            $table->string('description')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()
                ->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedBigInteger('legacy_parent_id')->nullable(); // remapped in pass 2

            $table->string('title');

            /*
             * type:
             *   page|post|course   -> resolved to a local record (polymorphic)
             *   route              -> a named route, e.g. news.index
             *   url                -> an explicit internal or external URL
             *   none               -> a dropdown parent with no destination
             *                         (Joomla stored these as link = '#')
             */
            $table->string('type', 20)->default('none');
            $table->nullableMorphs('linkable');        // linkable_type + linkable_id
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('target', 10)->default('_self');
            $table->string('css_class')->nullable();
            $table->string('icon')->nullable();

            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);
            $table->boolean('is_home')->default(false);

            // kept for auditing the 301 map and unresolved links
            $table->string('legacy_link')->nullable();
            $table->string('legacy_path')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
