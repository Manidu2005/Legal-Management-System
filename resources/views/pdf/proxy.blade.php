<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proxy</title>
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
            font-size: 28pt;
            font-weight: bold;
            letter-spacing: 6px;
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
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        .signature-block {
            display: inline-block;
            width: 45%;
            margin-top: 30px;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin-top: 70px;
            padding-top: 5px;
            font-size: 11pt;
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
        <h1>Proxy</h1>
        <div class="subtitle">Legal Instrument of Appointment</div>
    </div>

    <div class="reference">
        <strong>Case Reference:</strong> {{ $case->id }}<br>
        <strong>Date:</strong> {{ $generatedDate }}
    </div>

    <div class="content">
        <p><strong>KNOW ALL MEN BY THESE PRESENTS</strong> that:</p>

        <p>
            I, <span class="highlight">{{ $client->name }}</span>,
            bearing National Identity Card Number <span class="highlight">{{ $client->nic }}</span>,
            do hereby appoint, constitute and nominate as my lawful attorney and proxy,
            with full power and authority to act on my behalf in all matters
            relating to <strong>Case No. {{ $case->id }}</strong>.
        </p>

        <div class="section-title">Particulars of the Principal</div>
        <table class="details-table">
            <tr>
                <td>Full Name</td>
                <td>{{ $client->name }}</td>
            </tr>
            <tr>
                <td>NIC Number</td>
                <td>{{ $client->nic }}</td>
            </tr>
            <tr>
                <td>Case Reference</td>
                <td>{{ $case->id }}</td>
            </tr>
            <tr>
                <td>Case Type</td>
                <td>{{ ucfirst($case->case_type ?? 'N/A') }}</td>
            </tr>
        </table>

        <div class="section-title">Powers Granted</div>
        <p>
            The said proxy holder is hereby empowered and authorised to appear on my behalf
            before any Court of Law, Tribunal, or any other authority, and to make all such
            applications, representations, statements, and submissions as may be deemed
            necessary or expedient in connection with the aforementioned case.
        </p>

        <p>
            The proxy holder shall have full authority to sign, execute, and deliver all
            documents, pleadings, and instruments as may be required, and to do all acts,
            deeds, and things that may be necessary to effectuate the purposes of this proxy.
        </p>

        <p>
            I hereby ratify and confirm all that the said proxy holder may lawfully do or
            cause to be done by virtue of this instrument.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div class="signature-line">
                <strong>Signature of Principal</strong><br>
                {{ $client->name }}<br>
                NIC: {{ $client->nic }}
            </div>
        </div>
        <div class="signature-block" style="float: right;">
            <div class="signature-line">
                <strong>Witness</strong><br>
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
