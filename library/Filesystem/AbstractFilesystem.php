<?php

abstract class Filesystem_AbstractFilesystem
{
    public static function createFilesystem()
    {
        $filesystem = SpamFilter_Core::isWindows() ? new Filesystem_WindowsFilesystem() : new Filesystem_LinuxFilesystem();

        return $filesystem;
    }

    abstract public function removeDirectory($directory);
    abstract public function symlinkDirectory($target, $link);

    /**
     * Create a symbolic link
     * @see http://php.net/manual/en/function.symlink.php
     *
     * @param string $target
     * @param string $link
     *
     * @return bool
     */
    public function symlink($target, $link)
    {
        $new_link = null;

        if (is_link($link)) {
            $new_link = readlink($link);
            if ($target != $new_link) {
                unlink($link);
                $new_link = symlink($target, $link);
            }
        } else {
            $new_link = symlink($target, $link);
        }

        return $new_link;
    }
}