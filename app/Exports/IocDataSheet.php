<?php

namespace App\Exports;

use App\Models\Ioc;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class IocDataSheet implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithTitle,
    WithStrictNullComparison
{
    private array $rows;

    public function __construct(Collection $iocs)
    {
        $this->rows = $iocs->map(
            function (Ioc $ioc): array {

                // Kode tipe IOC
                $typeCodes = [
                    'url' => 1,
                    'domain' => 2,
                    'ip' => 3,
                    'sha256' => 4,
                    'md5' => 5,
                ];

                // Kode severity
                $severityCodes = [
                    'info' => 1,
                    'warning' => 2,
                    'critical' => 3,
                ];

                // Kode status
                $statusCode = $ioc->status === 'active'
                    ? 1
                    : 0;

                // Penanda false positive
                $falsePositiveCode =
                    $ioc->status === 'false_positive'
                    ? 1
                    : 0;

                // Kode sumber
                $sourceCode = $ioc->source === 'manual'
                    ? 1
                    : 2;

                return [

                    $ioc->id,

                    $typeCodes[$ioc->type] ?? 0,

                    $ioc->value,

                    $ioc->source_user,

                    $ioc->category,

                    implode(', ', $ioc->tags ?? []),

                    $severityCodes[$ioc->severity] ?? 1,

                    $statusCode,

                    $falsePositiveCode,

                    $ioc->source_date
                        ? $ioc->source_date
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d H:i:s')
                        : null,

                    $sourceCode,

                ];
            }
        )->all();
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [

            'ioc_id',

            'ioc_type_code',

            'ioc_value',

            'source_user',

            'category',

            'tag_list',

            'severity_code',

            'status_code',

            'false_positive_code',

            'source_date_wib',

            'source_code',

        ];
    }

    public function title(): string
    {
        return 'DATA';
    }
}