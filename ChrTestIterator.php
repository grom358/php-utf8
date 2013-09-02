<?php
/**
 * @copyright 2013 Cameron Zemek
 * @license MIT
 */

/**
 * Iterator for the UTF-8 encodings in data.bin
 */
class ChrTestIterator implements Iterator {
    private $file;
    private $codepoint = 0;
    private $char;

    public function __construct($filename = 'data.bin') {
        $this->file = fopen($filename, 'r');
    }

    public function __destruct() {
        fclose($this->file);
    }

    public function rewind() {
        rewind($this->file);
        $this->char = fread($this->file, 1);
        $this->codepoint = 0;
    }

    public function valid() {
        return !feof($this->file);
    }

    public function key() {
        return $this->codepoint;
    }

    public function current() {
        return $this->char;
    }

    public function next() {
        $this->codepoint++;
        $bytesToRead = 1;
        $codepoint = $this->codepoint;
        // Based on the codepoint we know how many bytes to read
        if ($codepoint >= 0x80 and $codepoint <= 0x7FF) $bytesToRead = 2;
        if ($codepoint >= 0x800 and $codepoint <= 0xFFFF) $bytesToRead = 3;
        if ($codepoint >= 0x10000 and $codepoint <= 0x10FFFF) $bytesToRead = 4;
        $this->char = fread($this->file, $bytesToRead);
    }
}
