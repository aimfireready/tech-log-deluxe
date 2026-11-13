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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->longText('body_text');
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->softDeletes();

            // Matches the timeline's sort and cursor (newest first, id as tie-breaker).
            $table->index(['occurred_at', 'id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('entry_tag', function (Blueprint $table) {
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['entry_id', 'tag_id']);
            $table->index('tag_id');
        });

        // Images are uploaded from the editor before the entry is saved, so entry_id starts null
        // and is filled in when an entry body references the file.
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('path')->unique();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('entry_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('entries');
    }
};
