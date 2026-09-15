{{-- Rodapé repetido pelo wkhtmltopdf em todas as páginas do contrato. --}}
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
</head>

<body style="margin: 0;">
    <div style="padding: 0 2mm;">
        <img src="{{ $brand['divider'] }}" style="display: block; width: 100%; height: 2mm;" alt="">
    </div>
    <div style="text-align: right; padding: 2mm 10mm 0 0;">
        <img src="{{ $brand['logo'] }}" style="height: 9mm;" alt="Fast Bar">
    </div>
</body>

</html>
