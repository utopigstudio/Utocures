<?php

use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_folders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });
        Schema::table('files', function (Blueprint $table) {
            $table->foreignUuid('employee_document_folder_id')->nullable()
                ->constrained('employee_document_folders')->restrictOnDelete();
        });
        foreach (['Contratos', 'Nóminas', 'Formación', 'Prevención', 'Otras'] as $name) {
            $id = (string) Str::uuid();
            DB::table('employee_document_folders')->insert([
                'id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($name === 'Otras') {
                DB::table('files')->where('fileable_type', (new Employee)->getMorphClass())
                    ->update(['employee_document_folder_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_document_folder_id');
        });
        Schema::dropIfExists('employee_document_folders');
    }
};
