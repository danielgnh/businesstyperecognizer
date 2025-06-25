<?php

use App\Enums\ScrapingJobStatus;
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
        Schema::create('scraping_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('job_type', 50); // 'website', 'social_media', 'google_business', 'analysis'
            $table->enum('status', ScrapingJobStatus::values())->default(ScrapingJobStatus::QUEUED->value);
            $table->integer('priority')->default(0);
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraint
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['priority', 'created_at']);
            $table->index('status');
            $table->index('job_type');
            $table->index('created_at');
            $table->index(['status', 'attempts', 'max_attempts']); // For retry queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_jobs');
    }
};
