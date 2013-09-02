<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */


/**
 * Return hexdump of the string
 *
 * @param string $str The input string
 * @return string Hexdump of the input string
 */
function hexdump($str) {
    $hex = '';
    for ($i = 0, $n = strlen($str); $i < $n; $i++) {
        $byte = $str[$i];
        $byteNo = ord($byte);
        $hex .= sprintf('%02X', $byteNo) . ' ';
    }
    return trim($hex);
}
