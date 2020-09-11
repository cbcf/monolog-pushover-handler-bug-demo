<?php

use Monolog\Handler\PushoverHandler;

/**
 * Try to actually read back the response from stream so we can keep the TLS/TCP connection alive.
 * This is just a dummy implementation which works for the current way pushover sends back the response.
 * If actually implemented, we would need a more robust HTTP response parser (handling e.g. Content-Length and
 * Transfer-Encoding headers).
 */
class PushoverHandlerWithKeepAlive extends PushoverHandler
{
    // Can be simplified, only required since user/users is private in PushoverHandler
    private $inWrite = false;

    public $debugResponses = [];

    protected function write(array $record): void
    {
        $this->inWrite = true;
        parent::write($record);
        $this->inWrite = false;
    }

    public function closeSocket(): void
    {
        $resource = $this->getResource();
        if ($this->inWrite && is_resource($resource)) {
            // Dummy implementation of HTTP/1 response reading
            $buffer = '';
            $head = null;
            $body = null;
            while(!feof($resource)) {
                // We need to concatenate multiple reads in case of chunked transfer
                $buffer .= @fread($resource, 64000);
                // See if we have a block HTTP separator (double CRLF)
                // If so, associate to head and body part
                // Note: This only works in this special case and should not be actually used!
                $blockBreakAt = strpos($buffer, "\r\n\r\n");
                if (false !== $blockBreakAt) {
                    if (null === $head) {
                        $head = substr($buffer, 0, $blockBreakAt);
                        $buffer = substr($buffer, $blockBreakAt+4);
                    } else {
                        $body = substr($buffer, 0, $blockBreakAt);
                        break;
                    }
                }
            }
            $this->debugResponses[] = [
                'head' => $head,
                'body' => $body,
            ];
        }

        // Keep stream alive (partially undo PR #148)
        if (!$this->inWrite) {
            parent::closeSocket();
        }
    }
}
