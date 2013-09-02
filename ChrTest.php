<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */

require_once dirname(__FILE__) . '/utf8.php';
require_once dirname(__FILE__) . '/hex.php';
require_once dirname(__FILE__) . '/ChrTestIterator.php';

/**
 * Test utf8_chr
 */
class ChrTest extends PHPUnit_Framework_TestCase {
    public function testChr() {
        $it = new ChrTestIterator();
        foreach ($it as $codepoint => $excepted) {
            $actual = utf8_chr($codepoint);
            $message = 'U+' . sprintf('%04X', $codepoint) . ' ; EXCEPTED: ' . hexdump($excepted) . ' ; ACTUAL: ' . hexdump($actual);
            $this->assertEquals($excepted, $actual, $message);
        }
    }
}
