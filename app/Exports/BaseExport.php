<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Illuminate\Support\Collection;

class BaseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    public function __construct(
        protected Collection $items,
        protected string $entity,
        protected array $fields,
        protected array $preRows = [] 
    ) { }

    public function collection()
    {
        return $this->items->map(function ($client) {
            $array = is_object($client) ? $client->toArray() : (array) $client;

            foreach ($this->fields as $field) {
                if (str_contains($field, '.')) {
                    $parts = explode('.', $field);
                    $value = $array;
                    foreach ($parts as $part) {
                        $value = $value[$part] ?? null;
                        if (is_null($value)) {
                            break;
                        }
                    }
                    $array[$field] = $value;
                }
            }

            $fields = array_flip($this->fields);

            foreach ($array as $key => $value) {
                if (!isset($fields[$key])) {
                    unset($array[$key]);
                    continue;
                }
                if (is_bool($value)) {
                    $array[$key] = $value ? trans('generic.yes') : trans('generic.no');
                }
            }

            if (isset($array['created_at'])) {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $array['created_at'])) {
                    $array['created_at'] = Carbon::createFromFormat('d/m/Y', $array['created_at'])->format('Y-m-d H:i');
                }
                $array['created_at'] = Carbon::parse($array['created_at'])->format('Y-m-d H:i');
            }

            if (isset($array['status'])) {
                $array['status'] = trans($this->entity . '.status_' . strtolower($array['status']));
            }

            $array = array_replace(array_fill_keys($this->fields, null), $array);

            return $array;
        });
    }

    public function headings(): array
    {
        $fieldNames = array_map(function($field) {
            if (str_contains($field, '.')) {
                $field = last(explode('.', $field));
            }
            return trans($this->entity . '.' . $field);
        }, $this->fields);

        return $fieldNames;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $worksheet = $event->sheet->getDelegate();

                foreach ($this->preRows as $i => $row) {
                    $worksheet->fromArray($row, null, 'A' . ($i + 1));
                }
            },
        ];
    }

    public function startCell(): string
    {
        return 'A' . (count($this->preRows) + 2);
    }
}
