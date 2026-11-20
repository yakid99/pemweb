<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class IocsExport implements
    WithMultipleSheets,
    WithProperties
{
    public function __construct(
        private Collection $iocs
    ) {
    }

    public function sheets(): array
    {
        return [

            // Sheet pertama: dataset untuk SPSS
            new IocDataSheet($this->iocs),

            // Sheet kedua: dokumentasi kode
            new IocCodebookSheet(),

        ];
    }

    public function properties(): array
    {
        return [

            'creator' => 'ThreatMetrics',

            'lastModifiedBy' => 'ThreatMetrics',

            'title' => 'Cyber Threat Intelligence Dataset',

            'subject' => 'IOC Dataset for IBM SPSS Statistics',

            'description' =>
                'Cyber Threat Intelligence dataset exported from ThreatMetrics',

            'keywords' => 'Cybersecurity, IOC, Threat Intelligence, SPSS',

            'category' => 'Cyber Threat Intelligence',

            'company' => 'ThreatMetrics',

        ];
    }
}