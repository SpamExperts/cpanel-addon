<?php

class Filesystem_WindowsFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        return shell_exec("rmdir \"$directory\" /s /q");
    }

    public function symlinkDirectory($target, $link)
    {
        $command = "MKLINK " . '"' . $link . '" "' . $target . '" /D';

        return shell_exec($command);
    }
}