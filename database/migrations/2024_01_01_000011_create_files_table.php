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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('hash')->nullable();
            $table->morphs('fileable');
            $table->uuid('uploaded_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['filename']);
            $table->index(['mime_type']);
            $table->index(['disk']);
            $table->index(['fileable_type', 'fileable_id']);
            $table->index(['uploaded_by']);
            $table->index(['hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
