<?php

namespace FFCMS\Traits;

/**
 * The class constructor should initialise a member oLog which contains
 * the method 'write' to write a line to the log entry - default file -  backend.
 * If an array is passed, each value is written as a new line.
 * Passing in an object dumps it to the log.
 */
trait Logger
{

    /**
     * @var \Log logging object
     */
    protected $oLog;

    /**
     * Write to log
     *
     * @param mixed $data
     * @return bool true on success
     */
    public function log($data)
    {
        $f3 = \Base::instance();
        if (empty($this->oLog) || empty($data)) {
            return false;
        }
        if (is_string($data)) {
            $data = [$data];
        } elseif (is_object($data)) {
            if (is_a($data, '\Exception') && $f3->get('DEBUG') > 2) {
                $data = print_r($data, 1);
            } else {
                $data = print_r($data, 1);
            }
        }
        if (is_array($data)) {
            foreach ($data as $line) {
                $this->oLog->write($line, $f3->get('log.date'));
            }
        }
        return true;
    }
}
