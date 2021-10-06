<?php

class Filesystem_LinuxFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        return shell_exec("rm -rf $directory");
    }

    public function symlinkDirectory($target, $link)
    {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        return shell_exec("ln -sf $target $link");
    }
}
