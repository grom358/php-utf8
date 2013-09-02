<?php
/**
 * Functions for working with UTF-8.
 *
 * @copyright 2013 Cameron Zemek
 * @license MIT
 * @link http://encoding.spec.whatwg.org/#utf-8 UTF-8 Decoding algorithm
 */

/*
 * NOTE: The decoding algorithm is copied and pasted in these functions for
 * performance reasons. My benchmarks using generic function for the algorithm
 * was 2 times slower.
 */

define('UNICODE_REPLACEMENT_CODEPOINT', 0xFFFD);
define('UTF8_REPLACEMENT_CHARACTER', "\xEF\xBF\xBD");

/**
 * Test if string is a valid UTF-8 string
 *
 * @param string $str The input string
 * @return boolean True if the string is valid UTF-8
 */
function utf8_validate($str) {
    static $regex = <<<'END'
/^(?:
    [\x00-\x7F]
    | [\xC2-\xDF][\x80-\xBF]
    | \xE0[\xA0-\xBF][\x80-\xBF]
    | [\xE1-\xEC\xEE-\xEF][\x80-\xBF]{2}
    | \xED[\x80-\x9F][\x80-\xBF]
    | \xF0[\x90-\xBF][\x80-\xBF]
    | [\xF1-\xF3][\x80-\xBF]{3}
    | \xF4[\x80-\x8F][\x80-\xBF]{2}
)*$/x
END;
    return preg_match($regex, $str) === 1;
}

/**
 * Convert a UTF-8 string that may contain invalid byte sequences into a valid
 * UTF-8 string.
 *
 * @param string $str The input string
 * @param array $errors Optional argument that is filled with array of byte
 *   position of invalid byte sequences
 * @return string A valid UTF-8 string
 */
function utf8_sanitize($str, &$errors = null) {
    if ($errors !== null) {
        $errors = array();
    }
    $utf8 = '';
    $bytesNeeded = 0;
    $bytesSeen = 0;
    $lowerBoundary = 0x80;
    $upperBoundary = 0xBF;
    $char = '';
    for ($pos = 0, $length = strlen($str); $pos < $length; $pos++) {
        $byte = ord($str[$pos]);
        if ($bytesNeeded == 0) {
            if ($byte >= 0x00 and $byte <= 0x7F) {
                $utf8 .= $str[$pos];
            } elseif ($byte >= 0xC2 and $byte <= 0xDF) {
                $bytesNeeded = 1;
            } elseif ($byte >= 0xE0 and $byte <= 0xEF) {
                if ($byte == 0xE0) {
                    $lowerBoundary = 0xA0;
                }
                if ($byte == 0xED) {
                    $upperBoundary = 0x9F;
                }
                $bytesNeeded = 2;
            } elseif ($byte >= 0xF0 and $byte <= 0xF4) {
                if ($byte == 0xF0) {
                    $lowerBoundary = 0x90;
                }
                if ($byte == 0xF4) {
                    $upperBoundary = 0x8F;
                }
                $bytesNeeded = 3;
            } else {
                $utf8 .= UTF8_REPLACEMENT_CHARACTER;
                if ($errors !== null) $errors[] = $pos;
            }
            $char = $str[$pos];
        } elseif ($byte < $lowerBoundary or $byte > $upperBoundary) {
            $char = '';
            $bytesNeeded = 0;
            $bytesSeen = 0;
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $utf8 .= UTF8_REPLACEMENT_CHARACTER;
            if ($errors !== null) $errors[] = $pos;
            $pos--;
        } else {
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $bytesSeen++;
            $char .= $str[$pos];
            if ($bytesSeen == $bytesNeeded) {
                $utf8 .= $char;
                $char = '';
                $bytesNeeded = 0;
                $bytesSeen = 0;
            }
        }
    }
    if ($bytesNeeded > 0) {
        $utf8 .= UTF8_REPLACEMENT_CHARACTER;
        if ($errors !== null) $errors[] = $length;
    }
    return $utf8;
}

/**
 * Return a specific character
 *
 * @param integer @num The unicode codepoint
 * @return string The UTF-8 character
 */
function utf8_chr($num) {
    if ($num < 0 or $num > 0x10FFFF) {
        return false;
    }
    if ($num <= 0x7F)       return chr($num);
    if ($num <= 0x7FF)      return chr(($num >> 6) | 0xC0) . chr(($num & 0x3F) | 0x80);
    if ($num <= 0xFFFF)     return chr(($num >> 12) | 0xE0) . chr((($num >> 6) & 0x3F) | 0x80) . chr(($num & 0x3F) | 0x80);
    if ($num <= 0x10FFFF)   return chr(($num >> 18) | 0xF0) . chr((($num >> 12) & 0x3F) | 0x80) . chr((($num >> 6) & 0x3F) | 0x80) . chr(($num & 0x3F) | 0x80);
}

/**
 * Return unicode codepoint of character
 *
 * @param string A single UTF-8 character
 * @return integer The unicode codepoint
 */
function utf8_ord($c) {
    $ord1 = ord($c[0]); if ($ord1 >= 0   && $ord1 <= 0x7F) return $ord1;
    $ord2 = ord($c[1]);
    $ord2 = ord($c[1]); if ($ord1 >= 0xC0 && $ord1 <= 0xDF) return (($ord1 - 0xC0) << 6) + ($ord2 - 0x80);
    $ord3 = ord($c[2]); if ($ord1 >= 0xE0 && $ord1 <= 0xEF) return (($ord1 - 0xE0) << 12) + (($ord2 - 0x80) << 6) + ($ord3 - 0x80);
    $ord4 = ord($c[3]); if ($ord1 >= 0xF0 && $ord1 <= 0xF4) return (($ord1 - 0xF0) << 18) + (($ord2 - 0x80) << 12) + (($ord3 - 0x80) << 6) + ($ord4 - 0x80);
}

/**
 * Return the formatted unicode for a character. eg. U+FE68
 *
 * @param string $c A single UTF-8 character
 * @return string The formatted unicode
 */
function utf8_code($c) {
    return 'U+' . sprintf('%04X', utf8_ord($c));
}

/**
 * Converts a UTF-8 string to an array
 *
 * @param string $str The input UTF-8 string
 * @param integer $split_length Maximum length in characters of the chunk
 * @return array Array containing chunks that are each $split_length in character length
 */
function utf8_split($str, $split_length = 1) {
    if ($split_length < 1) {
        return FALSE;
    }
    $chunks = array();
    $chunk = '';
    $chunkLength = 0;
    $bytesNeeded = 0;
    $bytesSeen = 0;
    $lowerBoundary = 0x80;
    $upperBoundary = 0xBF;
    $char = '';
    for ($pos = 0, $length = strlen($str); $pos < $length; $pos++) {
        $byte = ord($str[$pos]);
        if ($bytesNeeded == 0) {
            if ($byte >= 0x00 and $byte <= 0x7F) {
                $chunk .= $str[$pos];
                $chunkLength++;
            } elseif ($byte >= 0xC2 and $byte <= 0xDF) {
                $bytesNeeded = 1;
            } elseif ($byte >= 0xE0 and $byte <= 0xEF) {
                if ($byte == 0xE0) {
                    $lowerBoundary = 0xA0;
                }
                if ($byte == 0xED) {
                    $upperBoundary = 0x9F;
                }
                $bytesNeeded = 2;
            } elseif ($byte >= 0xF0 and $byte <= 0xF4) {
                if ($byte == 0xF0) {
                    $lowerBoundary = 0x90;
                }
                if ($byte == 0xF4) {
                    $upperBoundary = 0x8F;
                }
                $bytesNeeded = 3;
            } else {
                $utf8 .= UTF8_REPLACEMENT_CHARACTER;
            }
            $char = $str[$pos];
        } elseif ($byte < $lowerBoundary or $byte > $upperBoundary) {
            $char = '';
            $codepoint = 0;
            $bytesNeeded = 0;
            $bytesSeen = 0;
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $chunk .= UTF8_REPLACEMENT_CHARACTER;
            $chunkLength++;
            if ($errors !== null) $errors[] = $pos;
            $pos--;
        } else {
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $bytesSeen++;
            $char .= $str[$pos];
            if ($bytesSeen == $bytesNeeded) {
                $chunk .= $char;
                $chunkLength++;
                $char = '';
                $bytesNeeded = 0;
                $bytesSeen = 0;
            }
        }
        if ($chunkLength === $split_length) {
            $chunks[] = $chunk;
            $chunkLength = 0;
            $chunk = '';
        }
    }
    if ($bytesNeeded > 0) {
        $chunk .= UTF8_REPLACEMENT_CHARACTER;
        $chunkLength++;
        if ($errors !== null) $errors[] = $length;
    }
    if ($chunkLength > 0) {
        $chunks[] = $chunk;
    }
    return $chunks;
}

/**
 * Convert UTF-8 string into array of codepoints (ie. UTF-32)
 *
 * @param string $str The input UTF-8 string
 * @return array Array of codepoints
 */
function utf8_codepoints($str) {
    $out = array();
    $bytesNeeded = 0;
    $bytesSeen = 0;
    $codepoint = 0;
    $lowerBoundary = 0x80;
    $upperBoundary = 0xBF;
    $char = '';
    for ($pos = 0, $length = strlen($str); $pos < $length; $pos++) {
        $byte = ord($str[$pos]);
        if ($bytesNeeded == 0) {
            if ($byte >= 0x00 and $byte <= 0x7F) {
                $out[] = $byte;
            } elseif ($byte >= 0xC2 and $byte <= 0xDF) {
                $bytesNeeded = 1;
                $codepoint .= $byte - 0xC0;
            } elseif ($byte >= 0xE0 and $byte <= 0xEF) {
                if ($byte == 0xE0) {
                    $lowerBoundary = 0xA0;
                }
                if ($byte == 0xED) {
                    $upperBoundary = 0x9F;
                }
                $bytesNeeded = 2;
                $codepoint = $byte - 0xE0;
            } elseif ($byte >= 0xF0 and $byte <= 0xF4) {
                if ($byte == 0xF0) {
                    $lowerBoundary = 0x90;
                }
                if ($byte == 0xF4) {
                    $upperBoundary = 0x8F;
                }
                $bytesNeeded = 3;
                $codepoint = $byte - 0xF0;
            } else {
                $out[] = UNICODE_REPLACEMENT_CODEPOINT;
            }
            $char = $str[$pos];
            $codepoint = $codepoint << (6 * $bytesNeeded);
        } elseif ($byte < $lowerBoundary or $byte > $upperBoundary) {
            $char = '';
            $codepoint = 0;
            $bytesNeeded = 0;
            $bytesSeen = 0;
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $out[] = UNICODE_REPLACEMENT_CODEPOINT;
            $errors[$pos] = $str[$pos];
            $pos--;
        } else {
            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $bytesSeen++;
            $char .= $str[$pos];
            $codepoint = $codepoint + (($byte - 0x80) << (6 * ($bytesNeeded - $bytesSeen)));
            if ($bytesSeen == $bytesNeeded) {
                $out[] = $codepoint;
                $char = '';
                $bytesNeeded = 0;
                $bytesSeen = 0;
            }
        }
    }
    if ($bytesNeeded > 0) {
        $out[] = UNICODE_REPLACEMENT_CODEPOINT;
    }
    return $out;
}
