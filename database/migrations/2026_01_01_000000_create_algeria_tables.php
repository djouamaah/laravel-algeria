<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('algeria.tables');

        Schema::create($tables['wilayas'], function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->char('code', 2)->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->unsignedSmallInteger('parent_id')->nullable()->index();
        });

        Schema::create($tables['dairas'], function (Blueprint $table) use ($tables) {
            $table->unsignedSmallInteger('id')->primary();
            $table->unsignedSmallInteger('wilaya_id');
            $table->string('name');
            $table->string('name_ar');

            $table->foreign('wilaya_id')->references('id')->on($tables['wilayas'])->cascadeOnDelete();
        });

        Schema::create($tables['communes'], function (Blueprint $table) use ($tables) {
            $table->unsignedSmallInteger('id')->primary();
            $table->unsignedSmallInteger('daira_id');
            $table->unsignedSmallInteger('wilaya_id');
            $table->unsignedSmallInteger('legacy_wilaya_id')->index();
            $table->string('name');
            $table->string('name_ar');
            $table->char('postal_code', 5)->nullable()->index();

            $table->foreign('daira_id')->references('id')->on($tables['dairas'])->cascadeOnDelete();
            $table->foreign('wilaya_id')->references('id')->on($tables['wilayas'])->cascadeOnDelete();
        });

        Schema::create($tables['postal_codes'], function (Blueprint $table) use ($tables) {
            $table->char('code', 5)->primary();
            $table->unsignedSmallInteger('commune_id')->index();
            $table->string('name');
            $table->string('name_ar');

            $table->foreign('commune_id')->references('id')->on($tables['communes'])->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $tables = config('algeria.tables');

        Schema::dropIfExists($tables['postal_codes']);
        Schema::dropIfExists($tables['communes']);
        Schema::dropIfExists($tables['dairas']);
        Schema::dropIfExists($tables['wilayas']);
    }
};
