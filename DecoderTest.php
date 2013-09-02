<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */

require_once dirname(__FILE__) . '/utf8.php';
require_once dirname(__FILE__) . '/hex.php';
require_once dirname(__FILE__) . '/DecoderTestIterator.php';

/**
 * Test utf8_sanitize
 */
class DecoderTest extends PHPUnit_Framework_TestCase {
    public function testDecoder() {
        $it = new Utf8TestIterator();
        foreach ($it as $test) {
            $input = $test[0];
            $expected = $test[1];
            $actual = utf8_sanitize($input);
            $message = 'INPUT: ' . hexdump($input) . ' ; EXPECTED: ' . hexdump($expected) . ' ; ACTUAL: ' . hexdump($actual);
            $this->assertEquals($expected, $actual, $message);
        }
    }
}
