<?php


class CoreLog {
    private $path = null;
    private $FILE = null;
    private $dateFormat = null;

    public function __construct($file, $dateFormat) {
        $this->path = $file;
    $this->dateFormat = $dateFormat;
    }

    public function __destruct() {
        $this->closeFile();
    }

    private function openFile() {
        $this->FILE = fopen($this->path, 'a');
    }

    private function closeFile() {
        if($this->FILE !== null)
            fclose($this->FILE);
    }

    /**
     * Writes the debug output to the debug file
     *
     * @param   String    Debug message
     * @author  Lars Michelsen <lars@vertical-visions.de>
     */
    public function l($msg) {
        if($this->FILE === null)
            $this->openFile();

      fwrite($this->FILE, date($this->dateFormat).' '.$msg."\n");
    }
}