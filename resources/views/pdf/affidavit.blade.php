<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Affidavit</title>
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
        .numbered-item {
            margin-bottom: 14px;
            padding-left: 30px;
            text-indent: 0;
        }
        .numbered-item strong {
            margin-right: 8px;
        }
        .oath-section {
            background-color: #f9f9f9;
            border-left: 4px solid #333;
            padding: 15px 20px;
            margin: 25px 0;
            font-style: italic;
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
        <h1>Affidavit</h1>
        <div class="subtitle">Sworn Statement of Fact</div>
    </div>

    <div class="reference">
        <strong>Case Reference:</strong> {{ $case->id }}<br>
        <strong>Date:</strong> {{ $generatedDate }}
    </div>

    <div class="content">
        <p><strong>IN THE MATTER OF</strong> Case No. <span class="highlight">{{ $case->id }}</span></p>

        <div class="section-title">Particulars of the Deponent</div>
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

        <div class="section-title">Sworn Statement</div>

        <p>
            I, <span class="highlight">{{ $client->name }}</span>,
            bearing National Identity Card Number <span class="highlight">{{ $client->nic }}</span>,
            being the deponent herein, do hereby solemnly and sincerely declare and state
            on oath as follows:
        </p>

        <p class="numbered-item">
            <strong>1.</strong> That I am the above-named deponent and I make this affidavit
            of my own free will and volition, fully understanding the nature and consequences
            of this sworn statement.
        </p>

        <p class="numbered-item">
            <strong>2.</strong> That the facts and matters set forth herein are true and
            correct to the best of my knowledge, information and belief, and nothing
            material has been concealed or suppressed.
        </p>

        <p class="numbered-item">
            <strong>3.</strong> That this affidavit is made in support of proceedings in
            <strong>Case No. {{ $case->id }}</strong> ({{ ucfirst($case->case_type ?? 'N/A') }})
            and for such other purposes as may be required by law.
        </p>

        <p class="numbered-item">
            <strong>4.</strong> That I understand that making a false statement in this
            affidavit is punishable by law as perjury and I affirm the veracity of the
            contents herein.
        </p>

        <div class="oath-section">
            I, <strong>{{ $client->name }}</strong>, the deponent above named, do hereby
            swear/affirm that the contents of the above affidavit are true and correct
            to the best of my knowledge and belief, and that nothing material has been
            concealed therefrom.
        </div>

        <p style="text-indent: 0;">
            Sworn to and signed before me at ___________________
            on this _______ day of _______________ {{ date('Y') }}.
        </p>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div class="signature-line">
                <strong>Deponent</strong><br>
                {{ $client->name }}<br>
                NIC: {{ $client->nic }}
            </div>
        </div>
        <div class="signature-block" style="float: right;">
            <div class="signature-line">
                <strong>Commissioner for Oaths /<br>Justice of the Peace</strong><br>
                Name: ___________________
            </div>
        </div>
    </div>

    <div class="footer">
        Generated on {{ $generatedDate }} &mdash; LexLanka Legal Practice Management System
    </div>
</body>
</html>
