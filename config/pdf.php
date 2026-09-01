<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Binario do wkhtmltopdf
    |--------------------------------------------------------------------------
    |
    | Deixe vazio para a resolucao automatica: o pacote vendorado e usado
    | quando bate com a arquitetura da maquina e, se nao bater (por exemplo
    | container arm64), cai para o wkhtmltopdf instalado no PATH.
    | Preencha para apontar um caminho especifico.
    |
    */

    'binary' => env('WKHTMLTOPDF_BINARY'),

];
