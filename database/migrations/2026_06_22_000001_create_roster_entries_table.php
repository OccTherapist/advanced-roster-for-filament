<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('scope');
            $table->string('section_key');
            $table->morphs('assignee');
            $table->string('entry_type');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->json('color')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('roster_entries')->nullOnDelete();
            $table->string('repetition_pattern')->nullable();
            $table->unsignedInteger('repetition_value')->nullable();
            $table->string('repetition_interval')->nullable();
            $table->json('repetition_weekdays')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'section_key', 'start_at']);
            $table->index(['assignee_type', 'assignee_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_entries');
    }
};
