<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('legacy_parent_id')->nullable(); // remapped in pass 2
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
