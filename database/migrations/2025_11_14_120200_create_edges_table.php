<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('destination_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->unsignedInteger('avg_lead_time_days');
            $table->unsignedInteger('lead_time_std_days')->nullable();
            $table->unsignedInteger('volume')->nullable();
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'origin_node_id', 'destination_node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edges');
    }
};
