<?php

function slug($text) {
    $textWithOutSpecialChars = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    return strtolower(str_replace(' ', '-', $textWithOutSpecialChars));
}