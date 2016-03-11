<?php

class Filesystem_LinuxFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        system("rm -rf $directory");
    }

    public function symlinkDirectory($target, $link)
    {
        system("ln -sf $target $link", $return);

        return $return;
    }
}