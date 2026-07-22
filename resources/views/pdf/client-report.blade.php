<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Client Financial Report - Case #{{ $case->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.6;
            padding: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 16px;
            font-weight: normal;
            color: #4a4a4a;
            margin-bottom: 8px;
        }
        .header .meta {
            font-size: 11px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-grid td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            color: #555;
            width: 160px;
        }
        .info-grid .value {
            color: #1a1a1a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table thead th {
            background-color: #2c3e50;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.data-table tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .summary-box {
            background-color: #f4f6f9;
            border: 1px solid #d5d8dc;
            border-radius: 4px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .summary-box table {
            width: 100%;
        }
        .summary-box td {
            padding: 5px 0;
        }
        .summary-box .label {
            font-weight: bold;
            color: #555;
            width: 200px;
        }
        .summary-box .value {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }
        .summary-box .value.highlight {
            color: #2c3e50;
            font-size: 15px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
        .footer .disclaimer {
            font-style: italic;
            margin-bottom: 5px;
        }
        .amount { text-align: right; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-trust { background-color: #d4edda; color: #155724; }
        .badge-operational { background-color: #cce5ff; color: #004085; }
        .badge-trial { background-color: #f8d7da; color: #721c24; }
        .badge-calling { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚖ LEXLANKA</h1>
        <h2>Client Financial Report</h2>
        <div class="meta">
            Case Reference: #{{ $case->id }} | Generated: {{ $generatedAt->format('d F Y, h:i A') }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Client Information</div>
        <table class="info-grid">
            <tr>
                <td class="label">Client Name:</td>
                <td class="value">{{ $case->client->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">NIC:</td>
                <td class="value">{{ $case->client->nic ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Phone:</td>
                <td class="value">{{ $case->client->phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Case Type:</td>
                <td class="value">{{ $case->case_type ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Case Status:</td>
                <td class="value">{{ ucwords(str_replace('_', ' ', $case->status)) }}</td>
            </tr>
            <tr>
                <td class="label">Assigned Attorney:</td>
                <td class="value">{{ $case->assignedAttorney->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Financial Summary</div>
        <div class="summary-box">
            <table>
                <tr>
                    <td class="label">Trial Dates:</td>
                    <td class="value">{{ $trialDateCount }} dates × LKR {{ number_format($attorneyRate, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Appearance Fee:</td>
                    <td class="value highlight">LKR {{ number_format($summary['appearance_fee'], 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Client Trust Balance:</td>
                    <td class="value">LKR {{ number_format($summary['balances']['trust'], 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Operational Balance:</td>
                    <td class="value">LKR {{ number_format($summary['balances']['operational'], 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($summary['court_dates']->count() > 0)
    <div class="section">
        <div class="section-title">Court Dates</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Date</th>
                    <th style="width: 30%;">Type</th>
                    <th style="width: 40%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['court_dates'] as $courtDate)
                <tr>
                    <td>{{ $courtDate->date->format('d M Y, h:i A') }}</td>
                    <td>
                        <span class="badge {{ $courtDate->type === 'trial_date' ? 'badge-trial' : 'badge-calling' }}">
                            {{ str_replace('_', ' ', $courtDate->type) }}
                        </span>
                    </td>
                    <td>{{ $courtDate->date->isPast() ? 'Completed' : 'Upcoming' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($summary['ledger_entries']->count() > 0)
    <div class="section">
        <div class="section-title">Ledger Entries</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Date</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 25%;" class="amount">Amount (LKR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['ledger_entries'] as $entry)
                <tr>
                    <td>{{ $entry->created_at->format('d M Y') }}</td>
                    <td>
                        <span class="badge {{ $entry->type === 'trust' ? 'badge-trust' : 'badge-operational' }}">
                            {{ ucfirst($entry->type) }}
                        </span>
                    </td>
                    <td>{{ $entry->description }}</td>
                    <td class="amount">{{ number_format($entry->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p class="disclaimer">This report is for the client only. Firm-level totals are not included.</p>
        <p>LexLanka Legal Practice Management System | Report generated on {{ $generatedAt->format('d/m/Y') }}</p>
    </div>
</body>
</html>
