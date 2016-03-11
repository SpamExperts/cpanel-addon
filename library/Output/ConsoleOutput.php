<?php

class Output_ConsoleOutput implements Output_OutputInterface
{
    public function write($message, $prefix = null)
    {
        echo ($prefix) ? "[{$prefix}] {$message}\n" : "{$message}\n";
    }

    public function ok($message)
    {
        $this->write($message, 'OK');
    }

    public function warn($message)
    {
        $this->write($message, 'WARN');
    }

    public function error($message)
    {
        $this->write($message, 'ERROR');
    }

    public function info($message)
    {
        $this->write($message, 'INFO');
    }
}