<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('tier')->default('basic')->after('image'); // 'basic' | 'epic'
            $table->string('ability_name')->nullable()->after('ability_type');
            $table->text('ability_description')->nullable()->after('ability_name');
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['tier', 'ability_name', 'ability_description']);
        });
    }
};