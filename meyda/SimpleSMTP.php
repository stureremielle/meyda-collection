<?php
/**
 * SimpleSMTP - A lightweight SMTP client for PHP
 * Connects directly to SMTP servers via sockets.
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $debug = [];

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $htmlContent, $fromName = 'MeyDa Collection') {
        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $prefix = ($this->port == 465) ? 'ssl://' : '';
        $socket = @stream_socket_client($prefix . $this->host . ':' . $this->port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
        }

        $this->getResponse($socket); // Banner

        $this->sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        if ($this->port == 587) {
            $this->sendCommand($socket, "STARTTLS");
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return ['success' => false, 'error' => 'Failed to start TLS encryption'];
            }
            $this->sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        // Authentication
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->user));
        $res = $this->sendCommand($socket, base64_encode($this->pass));
        
        if (strpos($res, '235') === false) {
            return ['success' => false, 'error' => "Authentication failed: $res"];
        }

        // Message Flow
        $this->sendCommand($socket, "MAIL FROM: <" . $this->user . ">");
        $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
        $this->sendCommand($socket, "DATA");

        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=utf-8",
            "To: $to",
            "From: $fromName <" . $this->user . ">",
            "Subject: $subject",
            "Date: " . date('r'),
            "Message-ID: <" . time() . "." . uniqid() . "@" . $this->host . ">"
        ];

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $htmlContent . "\r\n.";
        $res = $this->sendCommand($socket, $data);

        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        if (strpos($res, '250') === false) {
            return ['success' => false, 'error' => "Failed to send data: $res"];
        }

        return ['success' => true];
    }

    private function sendCommand($socket, $cmd) {
        fputs($socket, $cmd . "\r\n");
        return $this->getResponse($socket);
    }

    private function getResponse($socket) {
        $response = "";
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>
