<?php

namespace App\Mail\Mailer;

use App\Models\SmtpSetting;
use Swift_Mailer;
use Swift_SmtpTransport;
use Illuminate\Support\Facades\Crypt;

class TenancyMailer extends Swift_Mailer
{

    public function __construct(SmtpSetting $smtpSettings)
    {
        $host = $smtpSettings->smtp_host;
        $port = $smtpSettings->smtp_port;
        $username = $smtpSettings->smtp_username;
        $password = $smtpSettings->smtp_password;
        $encryption = $smtpSettings->smtp_encryption;
        // Set SMTP transport
        $transport = (new Swift_SmtpTransport($host, $port, $encryption))
            ->setUsername($username)
            ->setPassword(Crypt::decryptString($password));

        parent::__construct($transport);
    }
}
