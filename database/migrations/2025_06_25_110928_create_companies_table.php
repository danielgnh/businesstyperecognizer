<?php

use App\Enums\CompanyClassification;
use App\Enums\CompanyStatus;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('domain')->nullable()->unique();
            $table->enum('status', CompanyStatus::values())->default(CompanyStatus::PENDING->value);
            $table->enum('classification', CompanyClassification::values())->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['classification', 'confidence_score']);
            $table->index('domain');
            $table->index('status');
            $table->index('classification');
            $table->index('last_analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
