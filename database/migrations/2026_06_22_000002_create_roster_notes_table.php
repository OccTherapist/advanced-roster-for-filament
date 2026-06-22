<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('scope');
            $table->date('date');
            $table->text('note');
            $table->json('color')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('roster_notes')->nullOnDelete();
            $table->string('repetition_pattern')->nullable();
            $table->unsignedInteger('repetition_value')->nullable();
            $table->string('repetition_interval')->nullable();
            $table->json('repetition_weekdays')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_notes');
    }
};
