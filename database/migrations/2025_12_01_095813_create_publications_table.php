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
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract');
            $table->longText('content');
            $table->string('image')->nullable();
            $table->enum('type', ['article', 'research-paper', 'book', 'report', 'other'])->default('article');
            $table->json('authors');
            $table->string('journal')->nullable();
            $table->string('publisher')->nullable();
            $table->date('publication_date');
            $table->string('doi')->nullable();
            $table->string('isbn')->nullable();
            $table->integer('citations')->default(0);
            $table->integer('downloads')->default(0);
            $table->integer('views')->default(0);
            $table->string('pdf_url')->nullable();
            $table->json('domains');
            $table->json('keywords')->nullable();
            $table->json('references')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
