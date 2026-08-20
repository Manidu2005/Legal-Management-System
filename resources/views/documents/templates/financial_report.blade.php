<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LexLanka — Firm Financial Summary Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1a202c; background: #fff; }

        /* Header */
        .header { background: #065f46; color: #fff; padding: 18px 28px; }
        .header-inner { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .header h1 { font-size: 18pt; font-weight: 700; letter-spacing: 2px; }
        .header .subtitle { font-size: 9pt; opacity: 0.8; margin-top: 3px; }
        .report-title { font-size: 13pt; font-weight: 700; }
        .report-date { font-size: 8pt; opacity: 0.8; margin-top: 4px; }

        /* Content */
        .content { padding: 20px 28px; }
        .meta { font-size: 8pt; color: #6b7280; margin-bottom: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }

        /* Summary Cards */
        .cards { display: table; width: 100%; border-collapse: separate; border-spacing: 10px; margin-bottom: 20px; }
        .card { display: table-cell; width: 33%; background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 6px; padding: 14px 16px; text-align: center; }
        .card.trust { background: #eff6ff; border-color: #bfdbfe; }
        .card.cases { background: #faf5ff; border-color: #ddd6fe; }
        .card .card-label { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .card .card-value { font-size: 16pt; font-weight: 700; color: #065f46; margin-top: 4px; }
        .card.trust .card-value { color: #1d4ed8; }
        .card.cases .card-value { color: #6d28d9; }

        /* Section */
        .section-title { font-size: 10pt; font-weight: 700; color: #065f46; border-bottom: 2px solid #d1fae5; padding-bottom: 4px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        thead th { background: #ecfdf5; color: #065f46; font-weight: 700; padding: 6px 8px; text-align: left; border-bottom: 2px solid #a7f3d0; font-size: 7.5pt; text-transform: uppercase; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody td { padding: 5px 8px; vertical-align: middle; }
        tbody td.right { text-align: right; font-weight: 600; }
        tbody td.center { text-align: center; }
        .status { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 7pt; font-weight: 700; }
        .s-active   { background: #d1fae5; color: #065f46; }
        .s-pending  { background: #dbeafe; color: #1e3a8a; }
        .s-trial    { background: #fef3c7; color: #92400e; }
        .s-closed   { background: #f3f4f6; color: #374151; }
        .s-judgment { background: #ede9fe; color: #4c1d95; }
        .positive { color: #059669; }
        .neutral  { color: #374151; }

        /* Totals row */
        tfoot tr { background: #ecfdf5 !important; border-top: 2px solid #a7f3d0; }
        tfoot td { padding: 6px 8px; font-weight: 700; font-size: 8.5pt; }

        /* Footer */
        .footer { margin-top: 20px; padding: 10px 28px; background: #f3f4f6; font-size: 7.5pt; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; }
        .disclaimer { margin-top: 10px; font-size: 7.5pt; color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-inner">
        <div class="header-left">
            <h1>⚖ LexLanka</h1>
            <div class="subtitle">Legal Practice Management System</div>
        </div>
        <div class="header-right">
            <div class="report-title">Firm Financial Summary Report</div>
            <div class="report-date">Generated: {{ $generatedAt }}</div>
        </div>
    </div>
</div>

<div class="content">

    <div class="meta">
        CONFIDENTIAL — For Senior Partner Use Only &nbsp;|&nbsp;
        Reporting Period: All Active Records &nbsp;|&nbsp;
        Report Date: {{ $generatedAt }}
    </div>

    {{-- Summary Cards --}}
    <table class="cards" style="margin-bottom:20px;">
        <tr>
            <td class="card">
                <div class="card-label">Total Operational Revenue</div>
                <div class="card-value">LKR {{ number_format($totalRevenue, 2) }}</div>
            </td>
            <td style="width:10px"></td>
            <td class="card trust">
                <div class="card-label">Total Client Trust Held</div>
                <div class="card-value">LKR {{ number_format($totalTrust, 2) }}</div>
            </td>
            <td style="width:10px"></td>
            <td class="card cases">
                <div class="card-label">Total Cases</div>
                <div class="card-value">{{ $totalCases }}</div>
            </td>
        </tr>
    </table>

    {{-- Case Breakdown Table --}}
    <div class="section-title">Case-by-Case Financial Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Case Ref</th>
                <th>Client</th>
                <th>Attorney</th>
                <th>Case Type</th>
                <th class="center">Status</th>
                <th class="center">Trial Dates</th>
                <th class="right">Appearance Fee (LKR)</th>
                <th class="right">Trust Balance (LKR)</th>
                <th class="right">Operational (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($caseSummaries as $item)
            @php
                $case   = $item['case'];
                $caseRef = 'LEX-' . $case->created_at->format('Y') . '-' . str_pad($case->id, 3, '0', STR_PAD_LEFT);
                $sCls   = match($case->status) {
                    'active'             => 's-active',
                    'pending'            => 's-pending',
                    'trial_scheduled'    => 's-trial',
                    'judgment_delivered' => 's-judgment',
                    default              => 's-closed',
                };
            @endphp
            <tr>
                <td><strong>{{ $caseRef }}</strong></td>
                <td>{{ $case->client->name ?? '—' }}</td>
                <td>{{ $case->assignedAttorney->name ?? '—' }}</td>
                <td>{{ $case->display_name }}</td>
                <td class="center"><span class="status {{ $sCls }}">{{ str_replace('_', ' ', ucfirst($case->status)) }}</span></td>
                <td class="center">{{ $item['trial_date_count'] }}</td>
                <td class="right">{{ number_format($item['appearance_fee'], 2) }}</td>
                <td class="right {{ $item['trust_balance'] > 0 ? 'positive' : 'neutral' }}">{{ number_format($item['trust_balance'], 2) }}</td>
                <td class="right {{ $item['operational_balance'] > 0 ? 'positive' : 'neutral' }}">{{ number_format($item['operational_balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" style="text-align:right; color:#065f46;">TOTALS</td>
                <td class="right" style="color:#1d4ed8;">{{ number_format($totalTrust, 2) }}</td>
                <td class="right" style="color:#059669;">{{ number_format($totalRevenue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="disclaimer">
        This report contains confidential financial information pertaining to LexLanka law firm. Unauthorized disclosure is prohibited.
        All amounts are in Sri Lankan Rupees (LKR).
    </div>
</div>

<div class="footer">
    LexLanka Legal Practice Management System &nbsp;|&nbsp; Firm Financial Summary &nbsp;|&nbsp; {{ $generatedAt }} &nbsp;|&nbsp; STRICTLY CONFIDENTIAL
</div>

</body>
</html>
