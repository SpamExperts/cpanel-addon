<?php

class Filesystem_WindowsFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        system("rmdir $directory /s /q");
    }

    public function symlinkDirectory($target, $link)
    {
        $command = "MKLINK " . '"' . $link . '" "' . $target . '" /D';
        system($command, $return);

        return $return;
    }
}