<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('billing_recipient')->nullable();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('billing_recipient')->nullable();
        });

        DB::table('invoices')->orderBy('id')->chunkById(200, function ($invoices): void {
            $clients = DB::table('clients')->whereIn('id', $invoices->pluck('client_id'))->get()->keyBy('id');
            foreach ($invoices as $invoice) {
                $client = $clients->get($invoice->client_id);
                if (! $client) {
                    continue;
                }
                DB::table('invoices')->where('id', $invoice->id)->update([
                    'billing_recipient' => json_encode([
                        'name' => $client->name,
                        'cif_nif' => $client->cif_nif,
                        'address' => $invoice->address,
                        'city' => $invoice->city,
                        'zip_code' => (string) $invoice->zip_code,
                        'country_id' => $invoice->country_id,
                        'email' => $client->email,
                    ], JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('billing_recipient');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('billing_recipient');
        });
    }
};
