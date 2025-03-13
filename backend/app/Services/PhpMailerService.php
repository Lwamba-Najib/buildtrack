<?php

namespace App\Services;

use App\Models\EmailSettings;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class PhpMailerService
{
    protected $mail;
    protected $emailSettings;

    public function __construct()
    {
        $this->mail = new PHPMailer(true); // Enable exceptions
        $this->emailSettings = $this->getEmailSettings();
        $this->configure();
    }

    protected function getEmailSettings()
    {
        // Fetch the email settings from the database
        return EmailSettings::first(); // Assuming there's only one row in the table
    }

    protected function configure()
    {
        if (!$this->emailSettings) {
            throw new \Exception('Email settings not found in the database.');
        }

        // Server settings
        $this->mail->isSMTP(); // Send using SMTP
        $this->mail->Host = $this->emailSettings->smtp_host; // Set the SMTP server to send through
        $this->mail->SMTPAuth = true; // Enable SMTP authentication
        $this->mail->Username = $this->emailSettings->smtp_username; // SMTP username
        $this->mail->Password = $this->emailSettings->smtp_password; // SMTP password
        $this->mail->SMTPSecure = $this->emailSettings->smtp_encryption; // Enable TLS encryption
        $this->mail->Port = $this->emailSettings->smtp_port; // TCP port to connect to

        // Sender settings
        $this->mail->setFrom($this->emailSettings->sender_email, $this->emailSettings->sender_name);
    }

    public function sendEmail($to, $subject, $body, $isHtml = true)
    {
        try {
            // Recipients
            $this->mail->addAddress($to); // Add a recipient

            // Content
            $this->mail->isHTML($isHtml); // Set email format to HTML
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;

            // Send the email
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error or handle it as needed
            Log::error('PHPMailer Error: ' . $this->mail->ErrorInfo);
            return false;
        }
    }
}
