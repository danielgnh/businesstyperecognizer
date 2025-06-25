<?php

use App\Enums\ClassificationMethod;
use App\Enums\CompanyClassification;
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
        Schema::create('classification_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->enum('classification', CompanyClassification::values());
            $table->decimal('confidence_score', 3, 2);
            $table->enum('method', ClassificationMethod::values())->default(ClassificationMethod::AUTOMATED->value);
            $table->json('reasoning');
            $table->uuid('classified_by')->nullable(); // User ID if manual
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('classified_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'classification']);
            $table->index('confidence_score');
            $table->index('method');
            $table->index('created_at');
            $table->index('classified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classification_results');
    }
};
