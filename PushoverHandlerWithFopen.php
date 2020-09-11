<?php


use Monolog\Handler\PushoverHandler;

class PushoverHandlerWithFopen extends PushoverHandler
{
    public $debugResponses = [];

    protected function generateDataStream(array $record): string
    {
        // We put our replacement Request/Response handler here, just proof-of-concept
        $rawRequest = parent::generateDataStream($record);
        $requestBody = explode("\r\n\r\n", $rawRequest, 2)[1];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header'  => [
                    'Content-type: application/x-www-form-urlencoded',
                    'Connection: close',
                ],
                'content' => trim($requestBody),
                'ignore_errors' => true,
            ],
        ]);

        $stream = @fopen('https://api.pushover.net/1/messages.json', 'r', false, $context);
        if (!$stream) {
            throw new RuntimeException("Pushover API call did not succeed. Cannot connect to endpoint.");
        }

        $responseHeaders = stream_get_meta_data($stream)['wrapper_data'];
        $body = stream_get_contents($stream);
        fclose($stream);

        $this->debugResponses[] = [
            'head' => $responseHeaders,
            'body' => $body,
        ];

        // Actually interpret Result
        if (false === strpos($responseHeaders[0] ?? '', '200 OK') ||
            false === strpos($body, '"status":1')) {
            throw new RuntimeException(sprintf("Pushover API call did not succeed: %s, Response: %s",
                $responseHeaders[0] ?? '', $body
            ));
        }

        // Bypass SocketHandlers writeToSocket method by writing empty string:
        return "";
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function closeSocket(): void
    {
        // No need to
    }

    protected function fsockopen()
    {
        return null;
    }

    protected function pfsockopen()
    {
        return null;
    }

    protected function streamSetChunkSize()
    {
        return true;
    }

    protected function streamSetTimeout()
    {
        return true;
    }

}