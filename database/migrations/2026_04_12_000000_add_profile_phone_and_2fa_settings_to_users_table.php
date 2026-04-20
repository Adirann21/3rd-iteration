<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email_hash');
            $table->boolean('two_fa_enabled')->default(false)->after('phone');
            $table->string('profile_picture')->nullable()->after('two_fa_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'two_fa_enabled', 'profile_picture']);
        });
    }
};
