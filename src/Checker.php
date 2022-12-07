<?php

namespace PruneMazui\MailAddressLiveChecker;


final class Checker
{
    private const DEFAULT_CONNECTIONS = [
        SmtpConnection::class,
    ];

    private const DEFAULT_FROM_ADDRESS = 'example@example.com';

    /**
     * @var ConnectionInterface[]
     */
    private array $connections = [];

    /**
     * @var string
     */
    private string $from_address = '';

    /**
     * @var string
     */
    private string $checkLog = '';

    /**
     * @param ConnectionInterface[] $connections
     */
    public function __construct(array $connections = [], string $from_address = '')
    {
        if (!empty($connections)) {
            foreach ($connections as $connection) {
                if ($connection instanceof ConnectionInterface) {
                    $this->connections[] = $connection;
                }
            }
        }

        if (empty($this->connections)) {
            foreach (self::DEFAULT_CONNECTIONS as $CONNECTION) {
                $this->connections[] = new $CONNECTION();
            }
        }

        $this->from_address = $from_address ?: self::DEFAULT_FROM_ADDRESS;
    }

    /**
     * @param string $mail_address
     *
     * @return bool
     */
    public function isLiveAddress(string $mail_address): bool
    {
        $this->checkLog = "Start checking address: {$mail_address}\n";

        $result = (function() use($mail_address) {
            $matches = [];

            if (!preg_match("/^([a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+)@([a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*)$/", $mail_address, $matches)) {
                $this->checkLog .= "address is invalid.\n";
                return false;
            }

            $hostname = $matches[2];
            $mx_records = MxRecordResolver::resolve($hostname);
            if (empty($mx_records)) {
                $this->checkLog .= "Host:{$hostname} MX Record is not found.\n";
                return false;
            }

            foreach ($mx_records as $mx_record) {
                foreach ($this->connections as $connection) {
                    $result = $connection->isLiveAddress($mx_record, $this->from_address, $mail_address);
                    $this->checkLog .= $connection->getLastCheckLog();
                    if ($result) {
                        return true;
                    }
                }
            }

            return false;
        })();

        $this->checkLog .= ($result ? "Address `{$mail_address}` is found." : "Address `{$mail_address}` is not found.") . "\n";
        return $result;
    }

    /**
     * @return string
     */
    public function getLastCheckLog(): string
    {
        return $this->checkLog;
    }
}
