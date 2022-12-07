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
        $matches = [];

        if (!preg_match("/^([a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+)@([a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*)$/", $mail_address, $matches)) {
            return false;
        }

        $hostname = $matches[2];
        $mx_records = MxRecordResolver::resolve($hostname);
        if (empty($mx_records)) {
            return false;
        }

        foreach ($mx_records as $mx_record) {
            foreach ($this->connections as $connection) {
                if ($connection->isLiveAddress($mx_record, $this->from_address, $mail_address)) {
                    return true;
                }
            }
        }

        return false;
    }
}
