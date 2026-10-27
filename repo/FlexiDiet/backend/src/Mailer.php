<?php
declare(strict_types=1);

function smtpRead($socket, array $expectedCodes): string {
    $response = '';
    $code = null;
    do {
        $line = fgets($socket, 515);
        if ($line === false) throw new RuntimeException('SMTP server closed the connection.');
        $response .= $line;
        if (!preg_match('/^(\d{3})([ -])/', $line, $match)) {
            throw new RuntimeException('SMTP server returned an invalid response.');
        }
        $code ??= $match[1];
        if ($match[1] !== $code) throw new RuntimeException('SMTP response code changed unexpectedly.');
    } while ($match[2] === '-');

    if (!in_array((int)$code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP rejected a command: ' . trim($response));
    }
    return $response;
}

function smtpWrite($socket, string $data): void {
    $offset = 0;
    $length = strlen($data);
    while ($offset < $length) {
        $written = fwrite($socket, substr($data, $offset));
        if ($written === false || $written === 0) throw new RuntimeException('Could not write to SMTP server.');
        $offset += $written;
    }
}

function smtpCommand($socket, string $command, array $expectedCodes): string {
    smtpWrite($socket, $command . "\r\n");
    return smtpRead($socket, $expectedCodes);
}

function sendFlexiDietEmail(string $recipient, string $subject, string $message): void {
    $config = require __DIR__ . '/../config.php';
    $host = trim((string)$config['smtp_host']);
    $port = (int)$config['smtp_port'];
    $security = strtolower(trim((string)$config['smtp_security']));
    $username = trim((string)$config['smtp_user']);
    $password = preg_replace('/\s+/', '', (string)$config['smtp_pass']);
    $from = trim((string)$config['smtp_from_email']);
    $fromName = trim((string)$config['smtp_from_name']);

    if ($host === '' || $port < 1 || $port > 65535 || !in_array($security, ['tls', 'ssl'], true)
        || $username === '' || $password === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP configuration is incomplete or invalid.');
    }
    foreach ([$username, $from, $fromName, $recipient, $subject] as $headerValue) {
        if (preg_match('/[\r\n]/', $headerValue)) throw new RuntimeException('SMTP message contains an invalid header.');
    }

    $context = stream_context_create(['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'peer_name' => $host,
    ]]);
    $target = ($security === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = stream_socket_client($target, $errorNumber, $errorMessage, 15, STREAM_CLIENT_CONNECT, $context);
    if ($socket === false) throw new RuntimeException("SMTP connection failed ($errorNumber): $errorMessage");
    stream_set_timeout($socket, 15);

    try {
        smtpRead($socket, [220]);
        $hello = gethostname() ?: 'localhost';
        smtpCommand($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $hello), [250]);
        if ($security === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('Could not establish SMTP TLS encryption.');
            }
            smtpCommand($socket, 'EHLO ' . preg_replace('/[^A-Za-z0-9.-]/', '', $hello), [250]);
        }
        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($username), [334]);
        smtpCommand($socket, base64_encode($password), [235]);
        smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);

        $encodedName = mb_encode_mimeheader($fromName, 'UTF-8', 'B', "\r\n");
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        $body = quoted_printable_encode(str_replace(["\r\n", "\r"], "\n", $message));
        $body = preg_replace('/^\./m', '..', $body);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $encodedName . ' <' . $from . '>',
            'To: <' . $recipient . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];
        smtpWrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . rtrim($body, "\r\n") . "\r\n.\r\n");
        smtpRead($socket, [250]);
        smtpCommand($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}
