<?php

namespace App\Http\Controllers;

use App\Models\Ioc;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ReportController extends Controller
{   

    public function index(Request $request)
    {
        $query = Ioc::query();

        // PENCARIAN IOC
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';

            $query->where(function ($q) use ($term) {
                $q->where('value', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('source_user', 'like', $term)
                    ->orWhereRaw('CAST(tags AS CHAR) LIKE ?', [$term]);
            });
        }

        // FILTER JENIS
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER TANGGAL AWAL
        if ($request->filled('date_from')) {
            $query->whereDate('source_date', '>=', $request->date_from);
        }

        // FILTER TANGGAL AKHIR
        if ($request->filled('date_to')) {
            $query->whereDate('source_date', '<=', $request->date_to);
        }

        // RINGKASAN STATISTIK
        $metrics = [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('status', 'active')->count(),

            'inactive' => (clone $query)
                ->where('status', 'inactive')->count(),

            'false_positive' => (clone $query)
                ->where('status', 'false_positive')->count(),

            'critical' => (clone $query)
                ->where('severity', 'critical')->count(),

            'warning' => (clone $query)
                ->where('severity', 'warning')->count(),

            'info' => (clone $query)
                ->where('severity', 'info')->count(),
        ];

        // DISTRIBUSI JENIS IOC
        $typeCounts = [];

        foreach (['url', 'domain', 'ip', 'sha256', 'md5'] as $type) {
            $typeCounts[$type] = (clone $query)
                ->where('type', $type)
                ->count();
        }

        // FILTER AKTIF
        $filters = $request->only([
            'search',
            'type',
            'status',
            'date_from',
            'date_to'
        ]);

        return view('reports.index', compact(
            'metrics',
            'typeCounts',
            'filters'
        ));
    }


    public function pdf(Request $request)
    {
        $query = Ioc::query();

        // FILTER PENCARIAN
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';

            $query->where(function ($q) use ($term) {
                $q->where('value', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('source_user', 'like', $term)
                    ->orWhereRaw(
                        'CAST(tags AS CHAR) LIKE ?',
                        [$term]
                    );
            });
        }

        // FILTER JENIS IOC
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER TANGGAL AWAL
        if ($request->filled('date_from')) {
            $query->whereDate(
                'source_date',
                '>=',
                $request->date_from
            );
        }

        // FILTER TANGGAL AKHIR
        if ($request->filled('date_to')) {
            $query->whereDate(
                'source_date',
                '<=',
                $request->date_to
            );
        }

        // AMBIL DATA IOC
        $iocs = $query
            ->orderByDesc('source_date')
            ->get();

        // RINGKASAN STATISTIK
        $metrics = [
            'total' => $iocs->count(),

            'active' => $iocs->where('status', 'active')->count(),

            'inactive' => $iocs->where('status', 'inactive')->count(),

            'false_positive' => $iocs->where('status', 'false_positive')->count(),

            'info' => $iocs->where('severity', 'info')->count(),

            'warning' => $iocs->where('severity', 'warning')->count(),

            'critical' => $iocs->where('severity', 'critical')->count(),
        ];

        // DISTRIBUSI JENIS IOC
        $typeCounts = [
            'url' => $iocs->where('type', 'url')->count(),
            'domain' => $iocs->where('type', 'domain')->count(),
            'ip' => $iocs->where('type', 'ip')->count(),
            'sha256' => $iocs->where('type', 'sha256')->count(),
            'md5' => $iocs->where('type', 'md5')->count(),
        ];

        // FILTER AKTIF
        $filters = [
            'type' => $request->type,
            'status' => $request->status,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'search' => $request->search,
        ];

        // WAKTU WIB
        $generatedAt = Carbon::now('Asia/Jakarta');

        // GENERATE PDF
        $pdf = Pdf::loadView('reports.pdf', compact(
            'iocs',
            'metrics',
            'typeCounts',
            'filters',
            'generatedAt'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-threatmetrics.pdf');
    }

    
    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL SPSS
    |--------------------------------------------------------------------------
    */

    public function spss(Request $request)
    {
        // AMBIL DATA BERDASARKAN FILTER
        $iocs = $this->getReportIocs($request);

        // KODE JENIS IOC
        $typeCodes = [
            'url' => 1,
            'domain' => 2,
            'ip' => 3,
            'sha256' => 4,
            'md5' => 5,
        ];

        $typeLabels = [
            'url' => 'URL',
            'domain' => 'Domain',
            'ip' => 'Alamat IP',
            'sha256' => 'SHA-256',
            'md5' => 'MD5',
        ];

        // KODE SEVERITY
        $severityCodes = [
            'info' => 1,
            'warning' => 2,
            'critical' => 3,
        ];

        $severityLabels = [
            'info' => 'Informasi',
            'warning' => 'Peringatan',
            'critical' => 'Kritis',
        ];

        // KODE STATUS
        $statusCodes = [
            'inactive' => 0,
            'active' => 1,
            'false_positive' => 2,
        ];

        $statusLabels = [
            'inactive' => 'Tidak Aktif',
            'active' => 'Aktif',
            'false_positive' => 'Positif Palsu',
        ];

        // KODE SUMBER
        $sourceCodes = [
            'tweetfeed' => 1,
            'manual' => 2,
        ];

        // BUAT WORKBOOK
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Data IOC');

        // HEADER KOLOM
        $headers = [
            'IOC_ID',
            'IOC_VALUE',
            'IOC_TYPE_CODE',
            'IOC_TYPE_LABEL',
            'CATEGORY',
            'SEVERITY_CODE',
            'SEVERITY_LABEL',
            'STATUS_CODE',
            'STATUS_LABEL',
            'SOURCE_CODE',
            'SOURCE_LABEL',
            'SOURCE_USER',
            'SOURCE_DATE_WIB',
            'TAGS',
            'SOURCE_URL',
        ];

        $sheet->fromArray($headers, null, 'A1');

        // ISI DATA
        $row = 2;

        foreach ($iocs as $ioc) {

            $type = strtolower($ioc->type ?? '');

            $severity = strtolower($ioc->severity ?? '');

            $status = strtolower($ioc->status ?? '');

            $source = strtolower($ioc->source ?? '');

            $tags = is_array($ioc->tags)
                ? implode(', ', $ioc->tags)
                : '';

            $sourceDate = $ioc->source_date
                ? $ioc->source_date
                    ->timezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s')
                : '';

            // ID NUMERIK
            $sheet->setCellValue('A' . $row, (int) $ioc->id);

            // NILAI IOC DISIMPAN SEBAGAI TEKS
            // AGAR IP DAN HASH TIDAK BERUBAH
            $sheet->setCellValueExplicit(
                'B' . $row,
                (string) $ioc->value,
                DataType::TYPE_STRING
            );

            // JENIS IOC
            $sheet->setCellValue(
                'C' . $row,
                $typeCodes[$type] ?? 0
            );

            $sheet->setCellValueExplicit(
                'D' . $row,
                $typeLabels[$type] ?? strtoupper($type),
                DataType::TYPE_STRING
            );

            // KATEGORI
            $sheet->setCellValueExplicit(
                'E' . $row,
                (string) ($ioc->category ?? ''),
                DataType::TYPE_STRING
            );

            // SEVERITY
            $sheet->setCellValue(
                'F' . $row,
                $severityCodes[$severity] ?? 0
            );

            $sheet->setCellValueExplicit(
                'G' . $row,
                $severityLabels[$severity] ?? strtoupper($severity),
                DataType::TYPE_STRING
            );

            // STATUS
            $sheet->setCellValue(
                'H' . $row,
                $statusCodes[$status] ?? -1
            );

            $sheet->setCellValueExplicit(
                'I' . $row,
                $statusLabels[$status] ?? strtoupper($status),
                DataType::TYPE_STRING
            );

            // SUMBER
            $sheet->setCellValue(
                'J' . $row,
                $sourceCodes[$source] ?? 0
            );

            $sheet->setCellValueExplicit(
                'K' . $row,
                $source ?: 'Lainnya',
                DataType::TYPE_STRING
            );

            // PENGGUNA SUMBER
            $sheet->setCellValueExplicit(
                'L' . $row,
                (string) ($ioc->source_user ?? ''),
                DataType::TYPE_STRING
            );

            // TANGGAL WIB
            $sheet->setCellValueExplicit(
                'M' . $row,
                $sourceDate,
                DataType::TYPE_STRING
            );

            // TAG
            $sheet->setCellValueExplicit(
                'N' . $row,
                $tags,
                DataType::TYPE_STRING
            );

            // URL SUMBER
            $sheet->setCellValueExplicit(
                'O' . $row,
                (string) ($ioc->source_tweet ?? ''),
                DataType::TYPE_STRING
            );

            $row++;
        }

        // FORMAT HEADER
        $lastRow = max(1, $row - 1);

        $sheet->getStyle('A1:O1')
            ->getFont()
            ->setBold(true);

        $sheet->freezePane('A2');

        $sheet->setAutoFilter('A1:O' . $lastRow);

        // LEBAR KOLOM
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('N')->setWidth(30);
        $sheet->getColumnDimension('O')->setWidth(40);

        // ====================================
        // SHEET KEDUA: KODE VARIABEL SPSS
        // ====================================

        $codeSheet = $spreadsheet->createSheet();

        $codeSheet->setTitle('Kode Variabel');

        $codebook = [
            ['NAMA VARIABEL', 'KETERANGAN', 'KODE'],

            ['IOC_ID', 'Identitas data IOC', 'Nomor unik'],

            [
                'IOC_TYPE_CODE',
                'Jenis indikator ancaman',
                '1=URL; 2=Domain; 3=IP; 4=SHA-256; 5=MD5'
            ],

            [
                'SEVERITY_CODE',
                'Tingkat keparahan',
                '1=Informasi; 2=Peringatan; 3=Kritis'
            ],

            [
                'STATUS_CODE',
                'Status indikator',
                '0=Tidak Aktif; 1=Aktif; 2=Positif Palsu'
            ],

            [
                'SOURCE_CODE',
                'Sumber data',
                '0=Lainnya; 1=TweetFeed; 2=Manual'
            ],

            ['IOC_VALUE', 'Nilai indikator', 'Teks'],

            ['CATEGORY', 'Kategori ancaman', 'Teks'],

            ['SOURCE_DATE_WIB', 'Tanggal pelaporan', 'YYYY-MM-DD HH:MM:SS'],

            ['TAGS', 'Tag ancaman', 'Teks'],

            ['SOURCE_USER', 'Akun sumber', 'Teks'],

            ['SOURCE_URL', 'Alamat sumber', 'Teks'],
        ];

        $codeSheet->fromArray($codebook, null, 'A1');

        $codeSheet->getStyle('A1:C1')
            ->getFont()
            ->setBold(true);

        foreach (range('A', 'C') as $column) {
            $codeSheet->getColumnDimension($column)->setAutoSize(true);
        }

        // PROPERTI FILE
        $spreadsheet->getProperties()
            ->setCreator('ThreatMetrics')
            ->setTitle('Dataset Cyber Threat Intelligence')
            ->setSubject('Dataset untuk IBM SPSS')
            ->setDescription('Ekspor data IOC ThreatMetrics');

        // GENERATE EXCEL
        $writer = new Xlsx($spreadsheet);

        $filename = 'ThreatMetrics_SPSS_' .
            Carbon::now('Asia/Jakarta')->format('Ymd_His') .
            '.xlsx';

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Cache-Control' => 'max-age=0',
            ]
        );
    }



    /*
    |--------------------------------------------------------------------------
    | EXPORT SPSS SYNTAX (.SPS)
    |--------------------------------------------------------------------------
    */

    public function spssSyntax(Request $request)
    {
        $syntax = <<<'SPSS'
    * ======================================================.
    * THREATMETRICS - CYBER THREAT INTELLIGENCE.
    * IBM SPSS VARIABLE CONFIGURATION.
    * ======================================================.

    * PETUNJUK PENGGUNAAN:
    * 1. Buka file ThreatMetrics_SPSS.xlsx di IBM SPSS.
    * 2. Pilih sheet Data IOC.
    * 3. Centang Read variable names from first row.
    * 4. Setelah data terbuka, buka file syntax ini.
    * 5. Klik Run > All.

    * ======================================================.
    * VARIABLE LABELS.
    * ======================================================.

    VARIABLE LABELS

    IOC_ID
    'Identitas unik indikator ancaman siber'

    IOC_VALUE
    'Nilai indikator ancaman siber'

    IOC_TYPE_CODE
    'Kode jenis indikator ancaman'

    IOC_TYPE_LABEL
    'Nama jenis indikator ancaman'

    CATEGORY
    'Kategori ancaman siber'

    SEVERITY_CODE
    'Kode tingkat keparahan ancaman'

    SEVERITY_LABEL
    'Nama tingkat keparahan ancaman'

    STATUS_CODE
    'Kode status indikator ancaman'

    STATUS_LABEL
    'Nama status indikator ancaman'

    SOURCE_CODE
    'Kode sumber data indikator'

    SOURCE_LABEL
    'Nama sumber data indikator'

    SOURCE_USER
    'Akun pengguna sumber informasi'

    SOURCE_DATE_WIB
    'Tanggal dan waktu pelaporan dalam WIB'

    TAGS
    'Tag atau label ancaman siber'

    SOURCE_URL
    'Alamat sumber informasi indikator'
    .


    * ======================================================.
    * VALUE LABELS - JENIS IOC.
    * ======================================================.

    VALUE LABELS IOC_TYPE_CODE
    0 'Tidak diketahui'
    1 'URL'
    2 'Domain'
    3 'Alamat IP'
    4 'SHA-256'
    5 'MD5'
    .


    * ======================================================.
    * VALUE LABELS - SEVERITY.
    * ======================================================.

    VALUE LABELS SEVERITY_CODE
    0 'Tidak diketahui'
    1 'Informasi'
    2 'Peringatan'
    3 'Kritis'
    .


    * ======================================================.
    * VALUE LABELS - STATUS.
    * ======================================================.

    VALUE LABELS STATUS_CODE
    -1 'Tidak diketahui'
    0 'Tidak Aktif'
    1 'Aktif'
    2 'Positif Palsu'
    .


    * ======================================================.
    * VALUE LABELS - SOURCE.
    * ======================================================.

    VALUE LABELS SOURCE_CODE
    0 'Lainnya atau tidak diketahui'
    1 'TweetFeed'
    2 'Manual'
    .


    * ======================================================.
    * VARIABLE MEASUREMENT LEVEL.
    * ======================================================.

    VARIABLE LEVEL

    IOC_ID (NOMINAL)
    IOC_VALUE (NOMINAL)
    IOC_TYPE_CODE (NOMINAL)
    IOC_TYPE_LABEL (NOMINAL)
    CATEGORY (NOMINAL)
    SEVERITY_CODE (ORDINAL)
    SEVERITY_LABEL (ORDINAL)
    STATUS_CODE (NOMINAL)
    STATUS_LABEL (NOMINAL)
    SOURCE_CODE (NOMINAL)
    SOURCE_LABEL (NOMINAL)
    SOURCE_USER (NOMINAL)
    SOURCE_DATE_WIB (NOMINAL)
    TAGS (NOMINAL)
    SOURCE_URL (NOMINAL)
    .


    * ======================================================.
    * NUMERIC FORMATS.
    * ======================================================.

    FORMATS
    IOC_ID (F12.0)
    IOC_TYPE_CODE (F2.0)
    SEVERITY_CODE (F2.0)
    STATUS_CODE (F2.0)
    SOURCE_CODE (F2.0)
    .


    * ======================================================.
    * EXECUTE CONFIGURATION.
    * ======================================================.

    EXECUTE.

    * MENAMPILKAN STRUKTUR VARIABEL.
    DISPLAY DICTIONARY.

    * ======================================================.
    * THREATMETRICS SPSS CONFIGURATION COMPLETED.
    * ======================================================.

    SPSS;

        $filename = 'ThreatMetrics_Syntax.sps';

        return response()->streamDownload(
            function () use ($syntax) {
                echo "\xEF\xBB\xBF";
                echo $syntax;
            },
            $filename,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }







        /*
        |--------------------------------------------------------------------------
        | EXPORT CSV
        |--------------------------------------------------------------------------
        */

    public function csv(Request $request)
    {
        $iocs = $this->getReportIocs($request);

        $typeCodes = [
            'url' => 1,
            'domain' => 2,
            'ip' => 3,
            'sha256' => 4,
            'md5' => 5,
        ];

        $severityCodes = [
            'info' => 1,
            'warning' => 2,
            'critical' => 3,
        ];

        $statusCodes = [
            'inactive' => 0,
            'active' => 1,
            'false_positive' => 2,
        ];

        $sourceCodes = [
            'tweetfeed' => 1,
            'manual' => 2,
        ];

        $filename = 'ThreatMetrics_CSV_' .
            Carbon::now('Asia/Jakarta')->format('Ymd_His') .
            '.csv';

        return response()->streamDownload(
            function () use (
                $iocs,
                $typeCodes,
                $severityCodes,
                $statusCodes,
                $sourceCodes
            ) {

                $output = fopen('php://output', 'w');

                // BOM AGAR KARAKTER INDONESIA TERBACA DI EXCEL
                fwrite($output, "\xEF\xBB\xBF");

                // HEADER CSV
                fputcsv($output, [
                    'IOC_ID',
                    'IOC_VALUE',
                    'IOC_TYPE_CODE',
                    'SEVERITY_CODE',
                    'STATUS_CODE',
                    'SOURCE_CODE',
                    'CATEGORY',
                    'SOURCE_DATE_WIB',
                    'TAGS',
                    'SOURCE_USER',
                    'SOURCE_URL',
                ], ';', '"', '\\');

                // DATA CSV
                foreach ($iocs as $ioc) {

                    $type = strtolower($ioc->type ?? '');

                    $severity = strtolower($ioc->severity ?? '');

                    $status = strtolower($ioc->status ?? '');

                    $source = strtolower($ioc->source ?? '');

                    $tags = is_array($ioc->tags)
                        ? implode(', ', $ioc->tags)
                        : '';

                    $sourceDate = $ioc->source_date
                        ? $ioc->source_date
                            ->timezone('Asia/Jakarta')
                            ->format('Y-m-d H:i:s')
                        : '';

                    fputcsv($output, [
                        $ioc->id,
                        $ioc->value,
                        $typeCodes[$type] ?? 0,
                        $severityCodes[$severity] ?? 0,
                        $statusCodes[$status] ?? -1,
                        $sourceCodes[$source] ?? 0,
                        $ioc->category ?? '',
                        $sourceDate,
                        $tags,
                        $ioc->source_user ?? '',
                        $ioc->source_tweet ?? '',
                    ], ',', '"', '\\');

                }

                fclose($output);

            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    




    /*
    |--------------------------------------------------------------------------
    | HELPER FILTER DATA LAPORAN
    |--------------------------------------------------------------------------
    */


    private function getReportIocs(Request $request)
    {
        $query = Ioc::query();

        // PENCARIAN
        if ($request->filled('search')) {

            $term = '%' . $request->search . '%';

            $query->where(function ($q) use ($term) {

                $q->where('value', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('source_user', 'like', $term)
                    ->orWhereRaw(
                        'CAST(tags AS CHAR) LIKE ?',
                        [$term]
                    );

            });
        }

        // FILTER JENIS
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // FILTER TANGGAL AWAL
        if ($request->filled('date_from')) {
            $query->whereDate(
                'source_date',
                '>=',
                $request->date_from
            );
        }

        // FILTER TANGGAL AKHIR
        if ($request->filled('date_to')) {
            $query->whereDate(
                'source_date',
                '<=',
                $request->date_to
            );
        }

        return $query
            ->orderByDesc('source_date')
            ->get();
    }

}