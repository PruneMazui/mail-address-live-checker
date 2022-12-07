<?php

namespace PruneMazui\MailAddressLiveChecker;

class SmtpConnection implements ConnectionInterface
{
    protected const PORT = 25;

    protected string $checkLog = '';

    /**
     * @return $this
     */
    protected function clearLog(): self
    {
        $this->checkLog = '';
        return $this;
    }

    /**
     * @param $log
     *
     * @return $this
     */
    protected function addLog($log): self
    {
        $this->checkLog .= trim($log). "\n";
        return $this;
    }

    /**
     * @param $mx_address
     *
     * @return resource
     */
    protected function createSocket($mx_address)
    {
        $this->addLog("Socket Connection {$mx_address}:" . self::PORT);
        return fsockopen($mx_address, self::PORT);
    }

    /**
     * @param resource $sock
     *
     * @return string
     */
    protected function read($sock): string
    {
        return fgets($sock,1024) ?: '';
    }

    /**
     * @param string $mx_address
     * @param string $from_address
     * @param string $to_address
     *
     * @return bool
     */
    public function isLiveAddress(string $mx_address, string $from_address, string $to_address): bool
    {
        $this->clearLog()->addLog("TRY MX:{$mx_address} TO:{$to_address} FROM:{$from_address}");
        $sock = $this->createSocket($mx_address);
        if (!$sock) {
            $this->addLog("Socket Connection Error");
            return false;
        }

        $command = "HELO {$mx_address}\r\n";
        $this->addLog("> {$command}");
        fputs($sock, $command);
        $response = $this->read($sock);
        $this->addLog("< {$response}");
        if (! str_starts_with($response, '220')) {
            fclose($sock);
            return false;
        }

        $command = "MAIL FROM:<$from_address>\r\n";
        $this->addLog("> {$command}");
        fputs($sock, $command);
        $response = $this->read($sock);
        $this->addLog("< {$response}");
        if (! str_starts_with($response, '250')) {
            fclose($sock);
            return false;
        }

        $command = "RCPT TO:<$to_address>\r\n";
        $this->addLog("> {$command}");
        fputs($sock, $command);
        $response = $this->read($sock);
        $this->addLog("< {$response}");
        fclose($sock);
        if (! str_starts_with($response, '250')) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getLastCheckLog(): string
    {
        return $this->checkLog;
    }
}
