<?php

use Monolog\Handler\PushoverHandler;

/**
 * Add call to fread after each request sent before closing the connection.
 */
class PushoverHandlerWithFread extends PushoverHandler
{
    // Can be simplified, only required since user/users is private in PushoverHandler
    private $inWrite = false;

    protected function write(array $record): void
    {
        $this->inWrite = true;
        parent::write($record);
        $this->inWrite = false;
    }

    public function closeSocket(): void
    {
        $res = $this->getResource();
        if ($this->inWrite && is_resource($res)) {
            // Read at least part of the response to ensure request actually reached pushover.
            @fread($res, 100);
        };

        parent::closeSocket();
    }

}
