<?php

namespace App\Infrastructure;

class BackgroundProcess
{
    private string $command;
    private int $pid;

    public function __construct(string $command = '')
    {
        $this->command  = $command;
    }

    public function run(string $stdOut = '/dev/null', bool $append = false, string $stdErr = "&1"): void
    {
        if($this->command === null) {
            return;
        }

        $this->pid = (int)shell_exec(sprintf('%s %s %s 2>>%s & echo $!', $this->command, ($append) ? '>>' : '>', $stdOut, $stdErr));
    }

    public function stop(): bool
    {
        return self::stopByPid($this->pid);
    }

    public static function stopByPid(int $pid): bool
    {
        try {
            $result = shell_exec(sprintf('kill %d 2>&1', $pid));
            if (!preg_match('/No such process/', $result)) {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    protected function setPid(int $pid)
    {
        $this->pid = $pid;
    }
}