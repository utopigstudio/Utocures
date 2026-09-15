<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('require_time_record_location')->default(false)->after('company_image');
        });

        Schema::create('employee_time_record_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_time_record_id')->constrained('employee_time_records')->cascadeOnDelete();
            $table->string('event_type', 8);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->timestamp('position_captured_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_time_record_id', 'event_type'], 'time_record_location_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_time_record_locations');

        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('require_time_record_location');
        });
    }
};
