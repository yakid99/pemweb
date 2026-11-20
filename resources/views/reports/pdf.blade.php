<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>ThreatMetrics CTI Report</title>

    <style>

        @page {
            margin: 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1E293B;
        }

        .header {
            border-bottom: 3px solid #0891B2;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 21px;
            font-weight: bold;
            color: #0F172A;
            letter-spacing: 2px;
        }

        .brand span {
            color: #0891B2;
        }

        .subtitle {
            color: #64748B;
            font-size: 9px;
            margin-top: 5px;
        }

        .report-title {
            margin-top: 20px;
            font-size: 15px;
            font-weight: bold;
        }

        .muted {
            color: #64748B;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 18px;
        }

        .summary td {
            width: 16.66%;
            padding: 10px;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
        }

        .metric-label {
            font-size: 8px;
            color: #64748B;
        }

        .metric-value {
            display: block;
            margin-top: 5px;
            font-size: 17px;
            font-weight: bold;
            color: #0F172A;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            color: #0F172A;
        }

        .distribution {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .distribution td {
            padding: 7px;
            border: 1px solid #E2E8F0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table th {
            background: #0F293D;
            color: white;
            font-size: 8px;
            text-align: left;
            padding: 8px 5px;
        }

        .data-table td {
            border: 1px solid #E2E8F0;
            padding: 6px 5px;
            font-size: 7px;
            word-wrap: break-word;
        }

        .data-table tr:nth-child(even) {
            background: #F8FAFC;
        }

        .critical {
            color: #DC2626;
            font-weight: bold;
        }

        .warning {
            color: #D97706;
            font-weight: bold;
        }

        .info {
            color: #0284C7;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #CBD5E1;
            padding-top: 8px;
            color: #64748B;
            font-size: 8px;
        }

    </style>

</head>

<body>

    <!-- HEADER -->

    <div class="header">

        <div class="brand">
            THREAT<span>METRICS</span>
        </div>

        <div class="subtitle">
            CYBER THREAT INTELLIGENCE REPORT
        </div>

        <div class="subtitle">
            Generated:
            {{ $generatedAt->format('d F Y H:i') }} WIB
        </div>

    </div>

    <!-- REPORT TITLE -->

    <div class="report-title">
        Cyber Threat Intelligence Overview
    </div>

    <p class="muted">
        Laporan ringkasan indikator ancaman siber
        berdasarkan data yang tersimpan pada sistem ThreatMetrics.
    </p>

    <!-- SUMMARY METRICS -->

    <table class="summary">

        <tr>

            <td>
                <div class="metric-label">TOTAL IOC</div>
                <span class="metric-value">
                    {{ number_format($metrics['total']) }}
                </span>
            </td>

            <td>
                <div class="metric-label">ACTIVE</div>
                <span class="metric-value">
                    {{ number_format($metrics['active']) }}
                </span>
            </td>

            <td>
                <div class="metric-label">INACTIVE</div>
                <span class="metric-value">
                    {{ number_format($metrics['inactive']) }}
                </span>
            </td>

            <td>
                <div class="metric-label">FALSE POSITIVE</div>
                <span class="metric-value">
                    {{ number_format($metrics['false_positive']) }}
                </span>
            </td>

            <td>
                <div class="metric-label">CRITICAL</div>
                <span class="metric-value critical">
                    {{ number_format($metrics['critical']) }}
                </span>
            </td>

            <td>
                <div class="metric-label">WARNING</div>
                <span class="metric-value warning">
                    {{ number_format($metrics['warning']) }}
                </span>
            </td>

        </tr>

    </table>

    <!-- SEVERITY INFO -->

    <div class="section-title">
        Threat Severity Summary
    </div>

    <table class="distribution">

        <tr>
            <td>INFO</td>
            <td>{{ number_format($metrics['info']) }}</td>

            <td>WARNING</td>
            <td>{{ number_format($metrics['warning']) }}</td>

            <td>CRITICAL</td>
            <td>{{ number_format($metrics['critical']) }}</td>
        </tr>

    </table>

    <!-- IOC DISTRIBUTION -->

    <div class="section-title">
        IOC Type Distribution
    </div>

    <table class="distribution">

        <tr>

            @foreach([
                'url',
                'domain',
                'ip',
                'sha256',
                'md5'
            ] as $type)

                <td>
                    <strong>{{ strtoupper($type) }}</strong>
                    <br><br>

                    {{ number_format(
                        $typeCounts[$type] ?? 0
                    ) }} records
                </td>

            @endforeach

        </tr>

    </table>

    <!-- FILTER INFORMATION -->

    <div class="section-title">
        Applied Filters
    </div>

    <p class="muted">

        Type:
        {{ $filters['type'] ?? 'All' }}

        |

        Status:
        {{ $filters['status'] ?? 'All' }}

        |

        Date From:
        {{ $filters['date_from'] ?? 'All' }}

        |

        Date To:
        {{ $filters['date_to'] ?? 'All' }}

        |

        Search:
        {{ $filters['search'] ?? 'None' }}

    </p>

    <!-- IOC TABLE -->

    <div class="section-title">
        IOC Records
        ({{ number_format($iocs->count()) }} Records)
    </div>

    <table class="data-table">

        <thead>

            <tr>

                <th style="width: 4%">ID</th>

                <th style="width: 8%">TYPE</th>

                <th style="width: 23%">IOC VALUE</th>

                <th style="width: 18%">TAGS</th>

                <th style="width: 9%">SEVERITY</th>

                <th style="width: 10%">STATUS</th>

                <th style="width: 12%">SOURCE USER</th>

                <th style="width: 16%">DATE (WIB)</th>

            </tr>

        </thead>

        <tbody>

            @forelse($iocs as $ioc)

                <tr>

                    <td>{{ $ioc->id }}</td>

                    <td>
                        {{ strtoupper($ioc->type) }}
                    </td>

                    <td>
                        {{ $ioc->value }}
                    </td>

                    <td>
                        {{ implode(', ', $ioc->tags ?? []) }}
                    </td>

                    <td class="{{ $ioc->severity }}">
                        {{ strtoupper($ioc->severity) }}
                    </td>

                    <td>
                        {{ strtoupper(
                            str_replace(
                                '_',
                                ' ',
                                $ioc->status
                            )
                        ) }}
                    </td>

                    <td>
                        {{ $ioc->source_user ?? 'Manual' }}
                    </td>

                    <td>
                        {{ $ioc->source_date
                            ? $ioc->source_date
                                ->timezone('Asia/Jakarta')
                                ->format('d/m/Y H:i')
                            : '-'
                        }}
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="8">
                        No IOC records found.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

    <!-- FOOTER -->

    <div class="footer">

        <strong>THREAT METRICS</strong>

        <br>

        Cyber Threat Intelligence Monitoring System.

        <br><br>

        Severity merupakan klasifikasi heuristik
        berbasis tag dan bukan skor CVSS resmi.
        IOC dari sumber komunitas perlu divalidasi
        sebelum digunakan untuk tindakan keamanan.

    </div>

</body>

</html>