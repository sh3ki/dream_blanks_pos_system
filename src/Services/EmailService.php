<?php

namespace App\Services;

class EmailService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->host        = $_ENV['MAIL_HOST']         ?? '';
        $this->port        = (int)($_ENV['MAIL_PORT']   ?? 587);
        $this->username    = $_ENV['MAIL_USERNAME']      ?? '';
        $this->password    = $_ENV['MAIL_PASSWORD']      ?? '';
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS']  ?? 'noreply@dreamblanks.com';
        $this->fromName    = $_ENV['MAIL_FROM_NAME']     ?? 'Dream Blanks POS';
    }

    public function sendOtp(string $to, string $otp, string $name): bool
    {
        $subject = 'Your Password Reset OTP - Dream Blanks POS';
        $body    = $this->otpTemplate($name, $otp);
        return $this->send($to, $subject, $body);
    }

    public function sendInvoice(string $to, array $invoice): bool
    {
        $subject = "Invoice #{$invoice['invoice_number']} - Dream Blanks";
        $body    = $this->invoiceTemplate($invoice);
        return $this->send($to, $subject, $body);
    }

    private function send(string $to, string $subject, string $body): bool
    {
        // If no SMTP configured, log and return
        if (empty($this->host)) {
            error_log("Email (no SMTP): To={$to} Subject={$subject}");
            return false;
        }

        $headers  = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        $headers .= "Reply-To: {$this->fromAddress}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $body, $headers);
    }

    private function otpTemplate(string $name, string $otp): string
    {
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
          <h2 style="color:#2D2D2D;">Password Reset OTP</h2>
          <p>Hello {$name},</p>
          <p>Your one-time password for resetting your Dream Blanks POS account password is:</p>
          <div style="background:#F5F5F5;padding:20px;text-align:center;font-size:36px;font-weight:bold;letter-spacing:8px;color:#0056B3;">
            {$otp}
          </div>
          <p>This OTP is valid for <strong>15 minutes</strong>.</p>
          <p>If you did not request this, please ignore this email.</p>
          <p style="color:#808080;font-size:12px;">Dream Blanks POS System</p>
        </div>
        HTML;
    }

    private function invoiceTemplate(array $invoice): string
    {
        $number = htmlspecialchars($invoice['invoice_number'] ?? '');
        $total  = number_format((float)($invoice['total_amount'] ?? 0), 2);
        return <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
          <h2>Invoice #{$number}</h2>
          <p>Please find your invoice attached.</p>
          <p><strong>Total Amount: &#8369;{$total}</strong></p>
          <p>Thank you for your business!</p>
          <p style="color:#808080;font-size:12px;">Dream Blanks POS System</p>
        </div>
        HTML;
    }
}
