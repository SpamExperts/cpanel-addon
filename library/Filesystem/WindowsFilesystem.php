<?php

class Filesystem_WindowsFilesystem extends Filesystem_AbstractFilesystem
{
    public function removeDirectory($directory)
    {
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        return shell_exec("rmdir \"$directory\" /s /q");
    }

    public function symlinkDirectory($target, $link)
    {
        $command = "MKLINK " . '"' . $link . '" "' . $target . '" /D';

        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.SystemExecFunctions.WarnSystemExec
        return shell_exec($command);
    }
}
