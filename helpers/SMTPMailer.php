<?php
/**
 * SMTPMailer
 * Sends email over SSL SMTP using PHP's built-in socket functions.
 * Handles multi-line SMTP responses correctly (RFC 5321).
 * No external libraries required. Works on WAMP / localhost.
 */

require_once __DIR__ . '/../config/mail.php';

class SMTPMailer {

    /**
     * Send a plain-text email via SMTP.
     *
     * @param string $to       Recipient email address
     * @param string $toName   Recipient display name
     * @param string $subject  Email subject
     * @param string $body     Plain-text body
     * @return bool            True on success, false on failure
     */
    public static function send($to, $toName, $subject, $body) {
        $host     = MAIL_HOST;
        $port     = MAIL_PORT;
        $user     = MAIL_USER;
        $pass     = MAIL_PASS;
        $fromAddr = MAIL_FROM_ADDR;
        $fromName = MAIL_FROM_NAME;

        // Open SSL socket
        $errno  = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "$host:$port",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            error_log("SMTPMailer: Cannot connect to $host:$port — $errstr ($errno)");
            return false;
        }

        // 10-second timeout per read
        stream_set_timeout($socket, 10);

        /**
         * Read a full SMTP response (handles multi-line like 250-...\r\n250 ...\r\n).
         * Returns the final status line.
         */
        $getResponse = function() use ($socket) {
            $response = '';
            while (true) {
                $line = fgets($socket, 515);
                if ($line === false) break;
                $response .= $line;
                // Multi-line: "250-..." means more lines follow; "250 ..." is the last
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $response;
        };

        /**
         * Send a command and read the response.
         */
        $cmd = function($command) use ($socket, $getResponse) {
            fwrite($socket, $command . "\r\n");
            return $getResponse();
        };

        /**
         * Extract numeric code from response line.
         */
        $code = function($response) {
            return (int) substr(trim($response), 0, 3);
        };

        try {
            // 220 greeting
            $resp = $getResponse();
            if ($code($resp) !== 220) {
                error_log("SMTPMailer: Bad greeting: " . trim($resp));
                fclose($socket);
                return false;
            }

            // EHLO
            $resp = $cmd("EHLO " . (gethostname() ?: 'localhost'));
            if ($code($resp) !== 250) {
                error_log("SMTPMailer: EHLO failed: " . trim($resp));
                fclose($socket);
                return false;
            }

            // AUTH LOGIN
            $resp = $cmd("AUTH LOGIN");
            if ($code($resp) !== 334) {
                error_log("SMTPMailer: AUTH LOGIN failed: " . trim($resp));
                fclose($socket);
                return false;
            }

            // Username
            $resp = $cmd(base64_encode($user));
            if ($code($resp) !== 334) {
                error_log("SMTPMailer: Username rejected: " . trim($resp));
                fclose($socket);
                return false;
            }

            // Password
            $resp = $cmd(base64_encode($pass));
            if ($code($resp) !== 235) {
                error_log("SMTPMailer: AUTH failed (wrong credentials?): " . trim($resp));
                fclose($socket);
                return false;
            }

            // MAIL FROM
            $resp = $cmd("MAIL FROM:<$fromAddr>");
            if ($code($resp) !== 250) {
                error_log("SMTPMailer: MAIL FROM rejected: " . trim($resp));
                fclose($socket);
                return false;
            }

            // RCPT TO
            $resp = $cmd("RCPT TO:<$to>");
            if ($code($resp) !== 250) {
                error_log("SMTPMailer: RCPT TO rejected: " . trim($resp));
                fclose($socket);
                return false;
            }

            // DATA
            $resp = $cmd("DATA");
            if ($code($resp) !== 354) {
                error_log("SMTPMailer: DATA rejected: " . trim($resp));
                fclose($socket);
                return false;
            }

            // Build message headers + body
            $toDisplay   = empty($toName) ? $to : self::encodeHeader($toName) . " <$to>";
            $fromDisplay = self::encodeHeader($fromName) . " <$fromAddr>";
            $subjectEnc  = self::encodeHeader($subject);
            $date        = date('r');
            $msgId       = '<' . uniqid('cirs_', true) . '@upsamail.edu.gh>';

            $msg  = "Date: $date\r\n";
            $msg .= "Message-ID: $msgId\r\n";
            $msg .= "From: $fromDisplay\r\n";
            $msg .= "To: $toDisplay\r\n";
            $msg .= "Subject: $subjectEnc\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n";
            $msg .= "\r\n";
            $msg .= chunk_split(base64_encode($body), 76, "\r\n");
            $msg .= "\r\n.";   // end-of-DATA marker

            fwrite($socket, $msg . "\r\n");
            $resp = $getResponse();

            $cmd("QUIT");
            fclose($socket);

            if ($code($resp) !== 250) {
                error_log("SMTPMailer: Message rejected after DATA: " . trim($resp));
                return false;
            }

            return true;

        } catch (Exception $e) {
            error_log("SMTPMailer exception: " . $e->getMessage());
            if ($socket) { try { fclose($socket); } catch (Exception $ex) {} }
            return false;
        }
    }

    /**
     * Encode a header value as UTF-8 Base64 (RFC 2047) if it contains non-ASCII chars.
     */
    private static function encodeHeader($value) {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}
