<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\EmployeeTimeRecord;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAutomaticInvoices extends Command
{
    protected $signature = 'invoices:generate-automatic';

    public function handle(): int
    {
        $ref = now()->startOfDay();
        $periodStart = $ref->copy()->startOfMonth();
        $periodEnd = $ref->copy()->endOfMonth();

        $this->info("Generando facturas automáticas para el periodo {$periodStart->toDateString()} - {$periodEnd->toDateString()}");

        $services = Service::all()->keyBy('id');

        Client::where('automatic_invoice', true)
            ->chunkById(200, function ($clients) use ($periodStart, $periodEnd, $services) {
                foreach ($clients as $client) {
                    $this->info("Generando factura automática para el cliente {$client->id} - {$client->name}");
                    $records = EmployeeTimeRecord::whereBetween('date_in', [$periodStart, $periodEnd])
                        ->whereHas('assignedHour', function ($q) use ($client) {
                            $q->where('client_id', $client->id);
                        })
                        ->with('assignedHour.service')
                        ->get();

                    if ($records->isNotEmpty()) {
                        $hoursByService = $records->groupBy(function ($record) {
                            return $record->assignedHour->service->id;
                        })->map(function ($group) {
                            return $group->sum(function ($record) {
                                $start = $record->date_in->copy()->setTimeFromTimeString($record->time_in);
                                $end = $record->date_out?->copy()->setTimeFromTimeString($record->time_out);

                                return $start->diffInMinutes($end, false) / 60;
                            });
                        });

                        $lines = [];
                        foreach ($hoursByService as $serviceId => $totalHours) {
                            $service = $services->get($serviceId);
                            $partnerDiscount = $client->is_partner ? $service->discount_partner : 0;
                            $price = $service->price * $totalHours;
                            $discountAmount = $price * ($partnerDiscount / 100);

                            $lines[] = [
                                'concept' => $service->name.' - Horas trabajadas en '.$periodStart->format('F Y'),
                                'quantity' => $totalHours,
                                'price' => $price,
                                'discount' => $discountAmount,
                                'tax_type' => 21,
                            ];
                        }

                        DB::transaction(function () use ($client, $lines) {
                            $invoice = Invoice::create([
                                'client_id' => $client->id,
                                'date' => now()->toDateString(),
                                'due_date' => now()->addDays(30)->toDateString(),
                            ]);

                            $invoice->lines()->createMany($lines);
                            $this->info("Factura automática generada: ID {$invoice->id} para el cliente {$client->id} - {$client->name}");
                        });
                    }
                }
            });

        $this->info('Proceso de generación de facturas automáticas completado.');

        return self::SUCCESS;
    }
}
