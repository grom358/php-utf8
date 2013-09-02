<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */

require_once dirname(__FILE__) . '/utf8.php';
require_once dirname(__FILE__) . '/hex.php';

/**
 * Test that utf8_chr and utf8_ord complement each other
 */
class ComplementTest extends PHPUnit_Framework_TestCase {
    private function helperComplement($codepoint) {
        $c = utf8_chr($codepoint);
        $n = utf8_ord($c);
        $this->assertEquals($codepoint, $n, "Not complement: U+" . sprintf('%04X', $codepoint) . ' ' . hexdump($c));
    }

    /**
     * Test complement of UTF-8 single byte encoded characters
     */
    public function testComplementOneByte() {
        for ($i = 0x0; $i <= 0x7F; $i++) {
            $this->helperComplement($i);
        }
    }

    /**
     * Test complement of UTF-8 two bytes encoded characters
     */
    public function testComplementTwoBytes() {
        for ($i = 0x80; $i <= 0x7FF; $i++) {
            $this->helperComplement($i);
        }
    }

    /**
     * Test complement of UTF-8 three bytes encoded characters
     */
    public function testComplementThreeBytes() {
        for ($i = 0x800; $i <= 0xFFFF; $i++) {
            $this->helperComplement($i);
        }
    }

    /**
     * Test complement of UTF-8 four bytes encoded characters
     */
    public function testComplementFourBytes() {
        for ($i = 0x10000; $i <= 0x10FFFF; $i++) {
            $this->helperComplement($i);
        }
    }
}
