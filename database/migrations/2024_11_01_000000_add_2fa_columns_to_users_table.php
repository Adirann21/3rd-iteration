<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('2fa_code')->nullable();
            $table->timestamp('2fa_expires_at')->nullable();
            $table->tinyInteger('2fa_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['2fa_code', '2fa_expires_at', '2fa_attempts']);
        });
    }
};

