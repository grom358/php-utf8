<?php
/**
 * Generate the test.bin binary file that contains data for testing of UTF-8
 * decoder algorithm utf8_sanitize
 *
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */

require_once dirname(__FILE__) . '/utf8.php';

/**
 * Generates UTF-8 decoder tests by creating a binary file that has the records
 * with the following format:
 *  - 16 bit little endian short integer containing length of input byte string
 *  - The UTF-8 bytes of the input string
 *  - 16 bit little endian short integer containing length of expected byte string
 *  - The expected UTF-8 bytes from utf8_sanitize
 */
class DecoderTestGenerator {
    private $file;
    private $patterns = array();

    public function __construct() {
        $this->file = fopen('test.bin', 'w');
    }

    public function __destruct() {
        fclose($this->file);
    }

    static private function randSequence($min, $max) {
        $codepoint = rand($min, $max);
        return utf8_chr($codepoint);
    }

    private function createTest($input, $excepted) {
        if (array_key_exists($input, $this->patterns)) {
            return false;
        }
        $this->patterns[$input] = true;
        fwrite($this->file, pack('v', strlen($input)));
        fwrite($this->file, $input);
        fwrite($this->file, pack('v', strlen($excepted)));
        fwrite($this->file, $excepted);
        return true;
    }

    public function generateValidSingleByte() {
        for ($i = 0; $i < 0x7F; $i++) {
            $str = utf8_chr($i);
            $this->createTest($str, $str);
        }
    }

    public function generateValidTwoBytes() {
        for ($i = 0x80; $i < 0x7FF; $i++) {
            $str = utf8_chr($i);
            $this->createTest($str, $str);
        }
    }

    public function generateValidThreeBytes() {
        // sampleSize = 0xD7FF - 0x800 = 53247
        for ($i = 0; $i < 1065 /* ~ 2% */;) {
            $str = self::randSequence(0x800, 0xD7FF);
            if ($this->createTest($str, $str)) $i++;
        }

        $description = 'Valid three bytes (0xE000-0xFFFF)';
        // sampleSize = 0xFFFF - 0xE000 = 8191
        for ($i = 0; $i < 164 /* ~ 2% */;) {
            $str = self::randSequence(0xE000, 0xFFFF);
            if ($this->createTest($str, $str)) $i++;
        }
    }

    public function generateValidFourBytes() {
        // sampleSize = 0x10FFFF - 0x10000 = 1048575
        for ($i = 0; $i < 1049 /* ~ 0.1% */;) {
            $str = self::randSequence(0x10000, 0x10FFFF);
            if ($this->createTest($str, $str)) $i++;
        }
    }

    public function generateInvalidStartByte() {
        $this->createTest("\xC0", UTF8_REPLACEMENT_CHARACTER);
        $this->createTest("\xC1", UTF8_REPLACEMENT_CHARACTER);
        for ($i = 0xF5; $i <= 0xFF; $i++) {
            $this->createTest(chr($i), UTF8_REPLACEMENT_CHARACTER);
        }
        for ($i = 0x80; $i <= 0xBF; $i++) {
            $this->createTest(chr($i), UTF8_REPLACEMENT_CHARACTER);
        }
    }

    public function generateOverlongTwoBytes() {
        $replacement = str_repeat(UTF8_REPLACEMENT_CHARACTER, 2);
        for ($i = 0xC0; $i <= 0xC1; $i++) {
            $byte1 = chr($i);
            for ($j = 0x80; $j <= 0xBF; $j++) {
                $byte2 = chr($j);
                $this->createTest($byte1 . $byte2, $replacement);
            }
        }
    }

    public function generateOverlongThreeBytes() {
        $replacement = str_repeat(UTF8_REPLACEMENT_CHARACTER, 3);
        $byte1 = chr(0xE0);
        for ($i = 0; $i < 100;) {
            $byte2 = chr(rand(0x80, 0x9F));
            $byte3 = chr(rand(0x80, 0xBF));
            if ($this->createTest($byte1 . $byte2 . $byte3, $replacement)) $i++;
        }
    }

    public function generateOverlongFourBytes() {
        $replacement = str_repeat(UTF8_REPLACEMENT_CHARACTER, 4);
        $byte1 = chr(0xF0);
        for ($i = 0; $i < 100;) {
            $byte2 = chr(rand(0x80, 0x8F));
            $byte3 = chr(rand(0x80, 0xBF));
            $byte4 = chr(rand(0x80, 0xBF));
            if ($this->createTest($byte1 . $byte2 . $byte3 . $byte4, $replacement)) $i++;
        }
    }

    public function generateSurrogates() {
        $replacement = str_repeat(UTF8_REPLACEMENT_CHARACTER, 3);
        $byte1 = chr(0xED);
        for ($i = 0; $i < 100;) {
            $byte2 = chr(rand(0xA0, 0xBF));
            $byte3 = chr(rand(0x80, 0xBF));
            if ($this->createTest($byte1 . $byte2 . $byte3, $replacement)) $i++;
        }
    }

    public function generateCodepointAboveMax() {
        $replacement = str_repeat(UTF8_REPLACEMENT_CHARACTER, 4);
        $byte1 = chr(0xF4);
        for ($i = 0; $i < 100;) {
            $byte2 = chr(rand(0x90, 0xBF));
            $byte3 = chr(rand(0x80, 0xBF));
            $byte4 = chr(rand(0x80, 0xBF));
            if ($this->createTest($byte1 . $byte2 . $byte3 . $byte4, $replacement)) $i++;
        }
    }
}

// Run all of the generateXXX() methods
$g = new DecoderTestGenerator();
$rc = new ReflectionClass('DecoderTestGenerator');
foreach ($rc->getMethods() as $method) {
    if (!$method->isAbstract() and $method->isPublic() and strpos($method->name, 'generate') === 0) {
        $method->invoke($g);
    }
}
