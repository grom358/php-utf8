<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */


require_once dirname(__FILE__) . '/utf8.php';
require_once dirname(__FILE__) . '/hex.php';
require_once dirname(__FILE__) . '/ChrTestIterator.php';

/**
 * Test utf8_ord
 */
class OrdTest extends PHPUnit_Framework_TestCase {
    public function testOrd() {
        $it = new ChrTestIterator();
        foreach ($it as $expectedCodepoint => $char) {
            $actual = utf8_ord($char);
            $message = 'INPUT: ' . hexdump($char) . ' ; EXCEPTED: U+' . sprintf('%04X', $expectedCodepoint) . ' ; ACTUAL: U+' . sprintf('%04X', $actual);
            $this->assertEquals($expectedCodepoint, $actual, $message);
        }
    }
}
