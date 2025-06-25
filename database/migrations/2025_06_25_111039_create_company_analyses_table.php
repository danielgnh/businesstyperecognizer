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
        Schema::create('company_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('data_source', 50); // 'website', 'social_media', 'google_business', 'partners'
            $table->json('raw_data');
            $table->json('processed_data');
            $table->json('indicators');
            $table->decimal('source_weight', 3, 2)->default(0.50);
            $table->decimal('source_confidence', 3, 2)->default(0.50);
            $table->timestamp('scraped_at')->useCurrent();

            // Foreign key constraint
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index(['company_id', 'data_source']);
            $table->index('scraped_at');
            $table->index('data_source');
            $table->index('source_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_analyses');
    }
};
