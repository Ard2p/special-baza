<!doctype html>
<html>
<head>
    <title>"Счет на оплату"</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body {
            font-family: DejaVu Sans;

            margin-left: auto;
            margin-right: auto;
            border: 1px #efefef solid;
            font-size: 11pt;
        }

        table.invoice_bank_rekv {
            border-collapse: collapse;
            border: 1px solid;
        }

        table.invoice_bank_rekv > tbody > tr > td, table.invoice_bank_rekv > tr > td {
            border: 1px solid;
            font-size: 12px;
        }

        table.invoice_items {
            border: 1px solid;
            border-collapse: collapse;
        }

        table.invoice_items td, table.invoice_items th {
            border: 1px solid;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
 @yield('content')
</body>
</html>
