<?php

function slug($text) {
    $textWithOutSpecialChars = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    return strtolower(str_replace(' ', '-', $textWithOutSpecialChars));
}


function brl($value) {
    if ($value === null || $value === '') {
        return 'Não informado';
    }

    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}