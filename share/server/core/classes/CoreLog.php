<?php


class CoreLog
{
    /** @var string|null */
    private $path = null;

    /** @var resource|false|null */
    private $FILE = null;

    /** @var string|null */
    private $dateFormat = null;

    /**
     * @param string $file
     * @param string $dateFormat
     */
    public function __construct($file, $dateFormat)
    {
        $this->path = $file;
        $this->dateFormat = $dateFormat;
    }

    public function __destruct()
    {
        $this->closeFile();
    }

    /**
     * @return void
     */
    private function openFile()
    {
        $this->FILE = fopen($this->path, 'a');
    }

    private function closeFile()
    {
        if ($this->FILE !== null) {
            fclose($this->FILE);
        }
    }

    /**
     * Writes the debug output to the debug file
     *
     * @param string $msg Debug message
     * @return void
     * @author  Lars Michelsen <lm@larsmichelsen.com>
     */
    public function l($msg)
    {
        if ($this->FILE === null) {
            $this->openFile();
        }

        fwrite($this->FILE, date($this->dateFormat) . ' ' . $msg . "\n");
    }
}