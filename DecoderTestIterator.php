<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */


/**
 * Iterator for the tests in test.bin
 */
class Utf8TestIterator implements Iterator {
    private $file;
    private $num = 0;
    private $current = array();

    public function __construct($filename = 'test.bin') {
        $this->file = fopen($filename, 'r');
    }

    public function __destruct() {
        fclose($this->file);
    }

    public function rewind() {
        rewind($this->file);
        $this->num = 0;
        $this->current = $this->readTest();
    }

    public function valid() {
        return !feof($this->file);
    }

    public function key() {
        return $this->num;
    }

    public function current() {
        return $this->current;
    }

    private function readShort() {
        $bytes = fread($this->file, 2);
        if ($bytes === false or $bytes === '') {
            return false;
        }
        $tmp = unpack('v', $bytes);
        return $tmp[1];
    }

    private function readString() {
        $length = $this->readShort();
        if ($length === false) {
            return false;
        }
        $this->pos += $length + 2;
        $str = fread($this->file, $length);
        return $str;
    }

    private function readTest() {
        $input = $this->readString();
        $expected = $this->readString();
        return array($input, $expected);
    }

    public function next() {
        $this->current = $this->readTest();
        $this->num++;
    }
}
