<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('username')->nullable()->unique()->after('name');
            $table->boolean('blocked')->default(false)->after('is_admin');
            $table->timestamp('last_login_at')->nullable()->after('blocked');

            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('legacy_source', 60)->nullable();
            $table->unique(['legacy_source', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['legacy_source', 'legacy_id']);
            $table->dropColumn([
                'is_admin', 'username', 'blocked', 'last_login_at',
                'legacy_id', 'legacy_source',
            ]);
        });
    }
};
