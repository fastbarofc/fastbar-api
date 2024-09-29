<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento - Buffet</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&family=Lato&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 50px 100px;
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .logo-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 10px;
        }

        .logo-title img {
            width: 100px;
            height: auto;
        }

        .hr-strong {
            border: none;
            border-top: 2px solid #aa8534;
            margin-top: 10px;
        }

        .hr {
            border: none;
            border-top: 1px solid #aa8534;
            margin-top: 10px;
        }

        .event-section {
            width: 100%;
            background-color: #f2f2f2;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .event-section td {
            vertical-align: top;
        }

        .event-row td:nth-child(odd) {
            font-weight: bold;
            text-align: left;
            width: 15%;
        }

        .event-row td:nth-child(even) {
            text-align: left;
            width: 35%;
        }

        .event-section,
        .event-section td {
            border: none;
        }

        .event-section tr {
            margin-bottom: 10px;
        }


        .bg-gold {
            padding: 1px 10px;
            color: white;
            background-color: #aa8534;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 10px;
        }

        .parthenon-info span {
            display: block;
            margin: 5px 0;
        }

        .footer-item {
            display: flex;
            align-items: center;
        }

        .footer-item span {
            margin-left: 8px;
        }

        .footer-component {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
    </style>
</head>

<body>
    <div style="display: flex; align-items: center; justify-content: space-between">
        <p>13/09/2024</p>
        <p class="bg-gold">ID: 1529</p>
    </div>
    <div class="logo-title">
        <h2 style="margin: 0px; padding: 0px">PROPOSTA CASA PARTHENON</h2>
        <img src="data:image/png;base64, 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" alt="Logo Casa Parthenon">
    </div>
    <hr class="hr-strong">
    </hr>
    <table class="event-section">
        <tr class="event-row">
            <td>Evento:</td>
            <td>Baile OAB</td>
            <td>Contato:</td>
            <td>31 9 9898 9099</td>
        </tr>
        <tr class="event-row">
            <td>Data:</td>
            <td>09/09/2025</td>
            <td>Local:</td>
            <td>Sítio OAB, Viçosa - MG</td>
        </tr>
        <tr class="event-row">
            <td>Horário:</td>
            <td>22h00 às 06h00 (08 horas de duração)</td>
            <td>Horário:</td>
            <td>22:00hs</td>
        </tr>
        <tr class="event-row">
            <td>Cliente:</td>
            <td>ZeniteEventos</td>
            <td>N° Convidados:</td>
            <td>900</td>
        </tr>
    </table>

    <div class="event-text">
        <p>
            O Casa Parthenon traz um novo conceito de culinária para festas e eventos, unindo tradição e inovação.
            Cada prato, dos clássicos reinventados às novas criações, é uma celebração da alta culinária e a
            valorização da nossa identidade gastronômica.
            A seguir descubra as possibilidades e novidades da nossa cozinha e viva uma experiência única. Preparese para uma jornada gastronômica incomparável.
        </p>
    </div>

    <hr class="hr-strong">
    <div class="footer">
        <div class="parthenon-info">
            <div class="footer-item">
                <svg style="color: #aa8534;" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                    <path fill="currentColor" d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                </svg>
                <span>(31) 3891-3891</span>
            </div>
            <div class="footer-item">
                <svg style="color: #aa8534;" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                    <path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                </svg>
                <span>(31) 9 9594-0046</span>
            </div>
            <div class="footer-item">
                <svg style="color: #aa8534;" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                    <path fill="currentColor" d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z" />
                </svg>
                <span>RUA ANTÔNIO LOPES LÉLIS, 199. SANTO ANTÔNIO, VIÇOSA-MG</span>
            </div>
        </div>
        <div class="logo">

            <span>exemplo logo</span>
        </div>
    </div>
</body>

</html>