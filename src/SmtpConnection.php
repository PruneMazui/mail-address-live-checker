<?php

namespace PruneMazui\MailAddressLiveChecker;

class SmtpConnection implements ConnectionInterface
{
    protected const PORT = 25;

    protected string $checkLog = '';

    /**
     * @param $mx_address
     *
     * @return resource
     */
    protected function createSocket($mx_address)
    {
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
        $sock = $this->createSocket($mx_address);
        if (!$sock) {
            return false;
        }

        fputs($sock,"HELO $mx_address\r\n");
        if (! str_starts_with($this->read($sock), '220')) {
            fclose($sock);
            return false;
        }

        fputs($sock,"MAIL FROM:<$from_address>\r\n");
        if (! str_starts_with($this->read($sock), '250')) {
            fclose($sock);
            return false;
        }

        fputs($sock,"RCPT TO:<$to_address>\r\n");
        if (! str_starts_with($this->read($sock), '250')) {
            fclose($sock);
            return false;
        }

        fclose($sock);
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
