<?php

namespace App\Notifications\Mailable;

use App\Mail\Mailer\TenancyMailer;
use App\Models\Company;
use App\Models\SmtpSetting;
use Illuminate\Mail\Mailable;
use Tenancy\Facades\Tenancy;
use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Factory as MailFactory;


class TenancyMailable extends Mailable
{
    private $smtpSettings;

    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function send($mailer)
    {
        $this->smtpSettings = $this->getSmtpSettings() ?: $this->getDefaultSmtpSettings();

        if (!empty($this->smtpSettings)) {
            $mailer->setSwiftMailer(new TenancyMailer($this->smtpSettings));
        }
        return $this->withLocale($this->locale, function () use ($mailer) {
            Container::getInstance()->call([$this, 'build']);

            $mailer = $mailer instanceof MailFactory
                ? $mailer->mailer($this->mailer)
                : $mailer;
            return $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                    ->buildRecipients($message)
                    ->buildSubject($message)
                    ->runCallbacks($message)
                    ->buildAttachments($message);
                if (!empty($this->getEntityKey())) {
                    $message->getHeaders()->addTextHeader('X-' . $this->getEntityKey(), $this->getEntityId());
                }
                if (!empty($this->getTenantId())) {
                    $message->getHeaders()->addTextHeader('X-Tenant-Id', $this->getTenantId());
                }
                if (!empty($this->getEventOnSuccess())) {
                    $message->getHeaders()->addTextHeader('X-Event', $this->getEventOnSuccess());
                }
                if (!empty($this->smtpSettings)) {
                    $message->from($this->smtpSettings->sender_email, $this->smtpSettings->sender_name);
                }
            });
        });
    }

    /**
     * Get smtp settings from witch email must be sent
     * @return SmtpSetting | null
     */
    protected function getSmtpSettings()
    {
        return null;
    }

    private function getDefaultSmtpSettings()
    {
        $company = Company::find($this->getTenantId());
        if ($company) {
            Tenancy::setTenant($company);
            return SmtpSetting::default()->first();
        }
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityKey(): string
    {
        return 'Invoice-Id';
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityId(): string
    {
        return '';
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getTenantId(): string
    {
        return '';
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getEventOnSuccess(): ?string
    {
        return null;
    }
}
