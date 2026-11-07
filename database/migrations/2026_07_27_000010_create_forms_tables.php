<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic form builder.
     *
     * NOTE: the legacy Joomla site had NO form extension and NO submissions —
     * `#__fields` is empty and no Visforms/RSForm tables exist. So nothing is
     * migrated here; these tables are seeded with a Contact form and a Course
     * Enquiry form. That is a capability the legacy site lacked, not a
     * migration, and it is reported as such.
     */
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('submit_label')->default('Submit');
            $table->text('success_message')->nullable();
            $table->json('notify_emails')->nullable();
            $table->boolean('store_submissions')->default(true);
            $table->boolean('published')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            $table->string('key');       // machine name used as the input name
            $table->string('label');

            /*
             * type: text | email | tel | number | textarea | select | radio
             *     | checkbox | date | file | hidden | heading
             */
            $table->string('type', 20)->default('text');

            $table->string('placeholder')->nullable();
            $table->string('help')->nullable();
            $table->json('options')->nullable();     // [{value,label}] for choice types
            $table->string('options_source')->nullable(); // e.g. 'courses' — options from the DB
            $table->json('validation')->nullable();  // {min:3,max:255,regex:'…'}
            $table->boolean('required')->default(false);
            $table->string('width', 10)->default('full'); // full|half
            $table->string('default_value')->nullable();

            $table->unsignedInteger('ordering')->default(0);
            $table->boolean('published')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);

            $table->timestamps();

            $table->unique(['form_id', 'key']);
            $table->index(['form_id', 'ordering']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->string('status', 20)->default('new'); // new|read|replied|spam
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
