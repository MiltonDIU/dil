<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Site identity, contact details, socials, theme colours and analytics.
     * Everything that used to live in Joomla's `configuration.php` or the
     * Helix Ultimate template params ends up here so an editor can change it.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->default('general')->index();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('type', 20)->default('text'); // text|textarea|html|image|color|boolean|number|url|email|json
            $table->text('value')->nullable();
            $table->json('options')->nullable();          // for select types
            $table->string('description')->nullable();
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['group', 'ordering']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
