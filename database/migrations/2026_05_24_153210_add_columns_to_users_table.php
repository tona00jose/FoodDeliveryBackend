<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('role')->nullable()->default(2)->comment('0:Administrator, 1:Restaurant Owner, 2:Customer')->after('email');
            $table->unsignedSmallInteger('is_super_admin')->nullable()->default(0)->comment('0:no, 1:yes')->after('role');
            $table->unsignedSmallInteger('is_blocked')->nullable()->default(0)->comment('0:enabled, 1:blocked')->after('is_super_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role', 'is_super_admin', 'is_blocked');
        });
    }
};
