<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deed of Transfer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            line-height: 1.8;
            color: #1a1a1a;
            padding: 60px 70px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px double #333;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 26pt;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 11pt;
            color: #555;
            font-style: italic;
        }
        .reference {
            text-align: right;
            margin-bottom: 30px;
            font-size: 11pt;
            color: #444;
        }
        .content {
            text-align: justify;
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 18px;
            text-indent: 40px;
        }
        .content p:first-child {
            text-indent: 0;
        }
        .highlight {
            font-weight: bold;
            text-decoration: underline;
        }
        .section-title {
            font-weight: bold;
            font-size: 14pt;
            margin: 30px 0 15px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #999;
            padding-bottom: 5px;
        }
        .details-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 8px 12px;
            vertical-align: top;
            font-size: 12pt;
        }
        .details-table td:first-child {
            width: 200px;
            font-weight: bold;
            color: #333;
        }
        .details-table td:last-child {
            border-bottom: 1px dotted #999;
        }
        .party-box {
            border: 1px solid #999;
            padding: 20px;
            margin: 15px 0;
        }
        .party-box h4 {
            font-size: 12pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            color: #444;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .party-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .party-box table td {
            padding: 5px 10px;
            font-size: 12pt;
        }
        .party-box table td:first-child {
            width: 180px;
            font-weight: bold;
            color: #444;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-block {
            display: inline-block;
            width: 45%;
            margin-top: 20px;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin-top: 70px;
            padding-top: 5px;
            font-size: 11pt;
        }
        .witness-section {
            margin-top: 40px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .witness-block {
            display: inline-block;
            width: 45%;
            vertical-align: top;
        }
        .witness-line {
            border-top: 1px solid #666;
            width: 220px;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 10pt;
        }
        .footer {
            margin-top: 50px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 9pt;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Deed of Transfer</h1>
        <div class="subtitle">Instrument of Conveyance and Transfer</div>
    </div>

    <div class="reference">
        <strong>Case Reference:</strong> {{ $case->id }}<br>
        <strong>Date:</strong> {{ $generatedDate }}<br>
        <strong>Deed No.:</strong> _______________
    </div>

    <div class="content">
        <p><strong>KNOW ALL MEN BY THESE PRESENTS</strong> that:</p>

        <p>
            This Deed of Transfer is made and entered into on this
            _______ day of _______________ {{ date('Y') }},
            in connection with <strong>Case No. {{ $case->id }}</strong>.
        </p>

        <div class="section-title">Parties to the Deed</div>

        {{-- Transferor --}}
        <div class="party-box">
            <h4>Transferor (Vendor / Grantor)</h4>
            <table>
                <tr>
                    <td>Full Name</td>
                    <td>{{ $client->name }}</td>
                </tr>
                <tr>
                    <td>NIC Number</td>
                    <td>{{ $client->nic }}</td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td>___________________________________________</td>
                </tr>
            </table>
        </div>

        {{-- Transferee --}}
        <div class="party-box">
            <h4>Transferee (Purchaser / Grantee)</h4>
            <table>
                <tr>
                    <td>Full Name</td>
                    <td>___________________________________________</td>
                </tr>
                <tr>
                    <td>NIC Number</td>
                    <td>___________________________________________</td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td>___________________________________________</td>
                </tr>
            </table>
        </div>

        <div class="section-title">Case Details</div>
        <table class="details-table">
            <tr>
                <td>Case Reference</td>
                <td>{{ $case->id }}</td>
            </tr>
            <tr>
                <td>Case Type</td>
                <td>{{ ucfirst($case->case_type ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td>Client Name</td>
                <td>{{ $client->name }}</td>
            </tr>
        </table>

        <div class="section-title">Transfer Provisions</div>

        <p>
            The Transferor hereby conveys, transfers, assigns and sets over unto
            the Transferee, all rights, title and interest in and to the property
            or subject matter described in the above-referenced case, free from
            all encumbrances, liens, and claims whatsoever.
        </p>

        <p>
            The Transferor warrants that they have good and marketable title to
            the property/subject matter and full right, power and authority to
            transfer the same, and that the Transferee shall have quiet and
            peaceable possession thereof.
        </p>

        <p>
            The consideration for this transfer is as agreed between the parties
            and as may be recorded in the proceedings of <strong>Case No. {{ $case->id }}</strong>.
        </p>

        <p style="text-indent: 0;">
            <strong>IN WITNESS WHEREOF</strong>, the parties hereto have set their hands
            and seals on the date first above written.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div class="signature-line">
                <strong>Transferor</strong><br>
                {{ $client->name }}<br>
                NIC: {{ $client->nic }}
            </div>
        </div>
        <div class="signature-block" style="float: right;">
            <div class="signature-line">
                <strong>Transferee</strong><br>
                Name: ___________________<br>
                NIC: ____________________
            </div>
        </div>
    </div>

    <div class="witness-section">
        <div class="section-title" style="margin-top: 0;">Witnesses</div>
        <div class="witness-block">
            <div class="witness-line">
                <strong>Witness 1</strong><br>
                Name: ___________________<br>
                NIC: ____________________
            </div>
        </div>
        <div class="witness-block" style="float: right;">
            <div class="witness-line">
                <strong>Witness 2</strong><br>
                Name: ___________________<br>
                NIC: ____________________
            </div>
        </div>
    </div>

    <div class="footer">
        Generated on {{ $generatedDate }} &mdash; LexLanka Legal Practice Management System
    </div>
</body>
</html>
