<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assigned_hours_templates', function (Blueprint $table) {
            $table->date('recurrence_anchor_date')->nullable()->after('date_start');
            $table->date('generated_until')->nullable()->after('date_end');
        });

        DB::table('assigned_hours_templates')
            ->select(['id', 'recurrency', 'date_start', 'created_at'])
            ->orderBy('id')
            ->get()
            ->each(function ($template) {
                $generatedUntil = DB::table('assigned_hours')
                    ->where('assigned_hours_template_id', $template->id)
                    ->max('date');

                DB::table('assigned_hours_templates')
                    ->where('id', $template->id)
                    ->update([
                        'recurrence_anchor_date' => (int) $template->recurrency > 0
                            ? Carbon::parse($template->date_start ?? $template->created_at)->toDateString()
                            : null,
                        'generated_until' => $generatedUntil,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('assigned_hours_templates', function (Blueprint $table) {
            $table->dropColumn(['recurrence_anchor_date', 'generated_until']);
        });
    }
};
