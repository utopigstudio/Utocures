<?php

use App\Models\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('email');
            $table->timestamp('invitation_accepted_at')->nullable()->after('role');
        });

        DB::table('users')
            ->whereNotIn('id', DB::table('employees')->select('user_id'))
            ->update([
                'role' => UserRole::ADMIN,
            ]);

        DB::table('users')
            ->whereIn('id', DB::table('employees')->select('user_id'))
            ->update([
                'role' => UserRole::EMPLOYEE,
            ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable(false)->change();
            $table->index('role');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'invitation_accepted_at']);
        });
    }
};
