<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Order;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tenancy\Facades\Tenancy;
use InvalidArgumentException;

class MarkUpNotification extends TenancyMailable
{
    use Queueable, SerializesModels;

    public string $companyId;
    protected string $projectId;
    protected string $orderId;
    protected string $markUp;
    protected string $number;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $companyId, string $projectId, string $orderId, string $markUp, string $number)
    {
        $this->companyId = $companyId;
        $this->projectId = $projectId;
        $this->orderId = $orderId;
        $this->markUp = $markUp;
        $this->number = $number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $lowPoint = $this->markUp < 35 ? 35 : 50;
        return $this->subject('Gross margin of order ' . $this->number . ' has reach a low point')
            ->markdown('emails.order_low_gross_margin_mail')
            ->with([
                'orderNumber' => $this->number,
                'markUp' => $this->markUp,
                'lowPoint' => $lowPoint,
                'url' => config('app.front_url') . '/' . $this->companyId . '/projects/' . $this->projectId . '/orders/' . $this->orderId . '/edit',
            ]);
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getTenantId(): string
    {
        return $this->companyId;
    }
}
