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
        Schema::table('companies', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('confidence_score');
            $table->string('branch')->nullable()->after('summary');
            $table->string('scope')->nullable()->after('branch');
            $table->json('keywords')->nullable()->after('scope');
            $table->timestamp('ai_analyzed_at')->nullable()->after('keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'summary',
                'branch',
                'scope',
                'keywords',
                'ai_analyzed_at',
            ]);
        });
    }
};
