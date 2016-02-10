<?php

namespace Filesystem;

class WindowsFilesystem extends AbstractFilesystem
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