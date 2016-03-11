<?php

interface Output_OutputInterface
{
    public function write($message, $prefix = null);
    public function ok($message);
    public function warn($message);
    public function error($message);
    public function info($message);
}