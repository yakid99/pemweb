<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class IocCodebookSheet implements
    FromArray,
    WithHeadings,
    ShouldAutoSize,
    WithTitle
{
    public function array(): array
    {
        return [

            // IOC TYPE
            ['ioc_type_code', 1, 'URL', 'Nominal'],
            ['ioc_type_code', 2, 'Domain', 'Nominal'],
            ['ioc_type_code', 3, 'IP', 'Nominal'],
            ['ioc_type_code', 4, 'SHA-256', 'Nominal'],
            ['ioc_type_code', 5, 'MD5', 'Nominal'],

            // SEVERITY
            ['severity_code', 1, 'INFO', 'Ordinal'],
            ['severity_code', 2, 'WARNING', 'Ordinal'],
            ['severity_code', 3, 'CRITICAL', 'Ordinal'],

            // STATUS
            ['status_code', 1, 'Active', 'Nominal'],
            ['status_code', 0, 'Not Active', 'Nominal'],

            // FALSE POSITIVE
            ['false_positive_code', 1, 'Yes', 'Nominal'],
            ['false_positive_code', 0, 'No', 'Nominal'],

            // SOURCE
            ['source_code', 1, 'Manual', 'Nominal'],
            ['source_code', 2, 'TweetFeed', 'Nominal'],

        ];
    }

    public function headings(): array
    {
        return [
            'variable_name',
            'numeric_code',
            'value_label',
            'measurement_level',
        ];
    }

    public function title(): string
    {
        return 'CODEBOOK';
    }
}