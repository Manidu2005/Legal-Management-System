<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Brief — {{ $caseRef }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a202c; background: #fff; }
        .header { background: #312e81; color: #fff; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20pt; font-weight: 700; letter-spacing: 2px; }
        .header .sub { font-size: 9pt; opacity: 0.8; margin-top: 3px; }
        .ref-badge { background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 11pt; font-weight: 700; letter-spacing: 1px; }
        .content { padding: 25px 30px; }
        .meta { font-size: 8pt; color: #6b7280; margin-bottom: 20px; }
        .section { margin-bottom: 22px; }
        .section-title { font-size: 11pt; font-weight: 700; color: #312e81; border-bottom: 2px solid #e0e7ff; padding-bottom: 4px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .grid-2 { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .field { margin-bottom: 8px; }
        .field label { font-size: 8pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
        .field span { font-size: 10pt; font-weight: 600; color: #1a202c; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 8pt; font-weight: 700; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #dbeafe; color: #1e3a8a; }
        .badge-trial { background: #fef3c7; color: #92400e; }
        .badge-closed { background: #f3f4f6; color: #374151; }
        .badge-judgment { background: #ede9fe; color: #4c1d95; }
        table { width: 100%; border-collapse: collapse; font-size: 9pt; }
        thead th { background: #eef2ff; color: #312e81; font-weight: 700; padding: 7px 10px; text-align: left; font-size: 8pt; text-transform: uppercase; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody td { padding: 6px 10px; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 30px; padding: 12px 30px; background: #f3f4f6; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; }
        .summary-box { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; }
        .summary-box .label { font-size: 8pt; color: #4f46e5; font-weight: 600; text-transform: uppercase; }
        .summary-box .value { font-size: 14pt; font-weight: 700; color: #312e81; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>⚖ LexLanka</h1>
        <div class="sub">Legal Practice Management System — Case Brief</div>
    </div>
    <div class="ref-badge">{{ $caseRef }}</div>
</div>

<div class="content">
    <div class="meta">Generated: {{ $generatedAt }} &nbsp;|&nbsp; Confidential — For Internal Use Only</div>

    {{-- Case Details --}}
    <div class="section">
        <div class="section-title">Case Information</div>
        <div class="grid-2">
            <div class="col">
                <div class="field"><label>Case Reference</label><span>{{ $caseRef }}</span></div>
                <div class="field"><label>Case Type</label><span>{{ $case->case_type ?: 'General Legal' }}</span></div>
                <div class="field">
                    <label>Status</label>
                    @php
                        $cls = match($case->status) {
                            'active'             => 'badge-active',
                            'pending'            => 'badge-pending',
                            'trial_scheduled'    => 'badge-trial',
                            'judgment_delivered' => 'badge-judgment',
                            default              => 'badge-closed',
                        };
                    @endphp
                    <span class="badge {{ $cls }}">{{ str_replace('_', ' ', ucfirst($case->status)) }}</span>
                </div>
            </div>
            <div class="col">
                <div class="field"><label>Client Name</label><span>{{ $case->client->name ?? '—' }}</span></div>
                <div class="field"><label>NIC</label><span>{{ $case->client->nic ?? '—' }}</span></div>
                <div class="field"><label>Client Phone</label><span>{{ $case->client->phone ?? '—' }}</span></div>
            </div>
        </div>
        <div class="field" style="margin-top:8px">
            <label>Assigned Attorney</label>
            <span>{{ $case->assignedAttorney->name ?? '—' }} &nbsp;({{ ucfirst($case->assignedAttorney->role ?? '') }})</span>
        </div>
    </div>

    {{-- Court Dates --}}
    <div class="section">
        <div class="section-title">Court Dates ({{ $case->courtDates->count() }})</div>
        @if($case->courtDates->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Type</th>
                    <th>Reminder</th>
                </tr>
            </thead>
            <tbody>
                @foreach($case->courtDates as $i => $cd)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $cd->date->format('d M Y') }}</td>
                    <td>{{ $cd->date->format('l') }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($cd->type)) }}</td>
                    <td>{{ $cd->reminder_sent ? '✓ Sent' : 'Pending' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p style="color:#6b7280; font-size:9pt;">No court dates scheduled.</p>
        @endif
    </div>

    {{-- Documents --}}
    <div class="section">
        <div class="section-title">Documents ({{ $case->documents->count() }})</div>
        @if($case->documents->isNotEmpty())
        <table>
            <thead>
                <tr><th>#</th><th>File</th><th>Type</th><th>Category</th><th>Uploaded</th></tr>
            </thead>
            <tbody>
                @foreach($case->documents as $i => $doc)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ basename($doc->file_path) }}</td>
                    <td>{{ strtoupper($doc->file_type) }}</td>
                    <td>{{ ucfirst($doc->category) }}</td>
                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p style="color:#6b7280; font-size:9pt;">No documents attached.</p>
        @endif
    </div>

    {{-- Ledger Summary --}}
    @if($case->ledgerEntries->isNotEmpty())
    <div class="section">
        <div class="section-title">Financial Entries ({{ $case->ledgerEntries->count() }})</div>
        <table>
            <thead>
                <tr><th>#</th><th>Type</th><th>Description</th><th style="text-align:right">Amount (LKR)</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach($case->ledgerEntries as $i => $entry)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ ucfirst($entry->type) }}</td>
                    <td>{{ $entry->description }}</td>
                    <td style="text-align:right">{{ number_format($entry->amount, 2) }}</td>
                    <td>{{ $entry->created_at->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="footer">
    LexLanka Legal Practice Management System &nbsp;|&nbsp; {{ $caseRef }} &nbsp;|&nbsp; Generated {{ $generatedAt }} &nbsp;|&nbsp; CONFIDENTIAL
</div>

</body>
</html>
