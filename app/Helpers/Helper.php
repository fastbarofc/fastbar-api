<?php

function slug($text) {
    $textWithOutSpecialChars = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    return strtolower(str_replace(' ', '-', $textWithOutSpecialChars));
}


function currencyMask($value) {
    if ($value === null || $value === '') {
        return 'Não informado';
    }

    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}
/**
 * Remove a máscara do CPF, mantendo apenas os 11 dígitos.
 */
function unmaskCpf($cpf)
{
    if ($cpf === null || $cpf === '') {
        return null;
    }

    return substr(preg_replace('/\D/', '', $cpf), 0, 11) ?: null;
}

/**
 * Remove a máscara do CNPJ mantendo letras, para atender o novo formato alfanumérico
 * da Receita Federal: os 12 primeiros caracteres são alfanuméricos e os 2 últimos
 * (dígitos verificadores) são sempre numéricos.
 */
function unmaskCnpj($cnpj)
{
    if ($cnpj === null || $cnpj === '') {
        return null;
    }

    $value = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($cnpj)), 0, 14);

    if (strlen($value) > 12) {
        $value = substr($value, 0, 12) . preg_replace('/\D/', '', substr($value, 12));
    }

    return $value ?: null;
}

/**
 * Formata o CPF para exibição: 000.000.000-00
 */
function maskCpf($cpf)
{
    $value = unmaskCpf($cpf);

    if (strlen((string) $value) !== 11) {
        return $value;
    }

    return substr($value, 0, 3) . '.' . substr($value, 3, 3) . '.' . substr($value, 6, 3) . '-' . substr($value, 9, 2);
}

/**
 * Formata o CNPJ (numérico ou alfanumérico) para exibição: AA.AAA.AAA/AAAA-00
 */
function maskCnpj($cnpj)
{
    $value = unmaskCnpj($cnpj);

    if (strlen((string) $value) !== 14) {
        return $value;
    }

    return substr($value, 0, 2) . '.' . substr($value, 2, 3) . '.' . substr($value, 5, 3)
        . '/' . substr($value, 8, 4) . '-' . substr($value, 12, 2);
}

/**
 * Formata CPF ou CNPJ conforme o tamanho do documento.
 */
function maskDocument($document)
{
    if ($document === null || $document === '') {
        return null;
    }

    return strlen(preg_replace('/[^A-Z0-9]/', '', strtoupper($document))) > 11
        ? maskCnpj($document)
        : maskCpf($document);
}
