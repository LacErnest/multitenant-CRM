<?php

namespace App\Http\Resources\Settings;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class SmtpSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id'                                => $this->id,
          'smtp_host'                         => $this->smtp_host,
          'smtp_port'                         => $this->smtp_port,
          'smtp_username'                     => $this->smtp_username,
          'smtp_password'                     => null,
          'smtp_encryption'                   => $this->smtp_encryption,
          'sender_email'                      => $this->sender_email,
          'sender_name'                       => $this->sender_name,
          'default'                           => $this->default,
          'created_at'                        => $this->created_at,
          'updated_at'                        => $this->updated_at,
          'deleted_at'                        => $this->deleted_at,
        ];
    }
}
