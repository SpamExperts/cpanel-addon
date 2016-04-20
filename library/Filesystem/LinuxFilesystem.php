<?php

class Filesystem_LinuxFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        return shell_exec("rm -rf $directory");
    }

    public function symlinkDirectory($target, $link)
    {
        return shell_exec("ln -sf $target $link");
    }
}