<?php

namespace App\Http\Resources\Analytics;

use Illuminate\Http\Resources\Json\JsonResource;

class EarnOutStatusResource extends JsonResource
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
          'id'            => $this->id,
          'quarter'       => convertTimestampToQuarterString(date('n', $this->quarter), date('Y', $this->quarter)),
          'approved'      => $this->approved,
          'confirmed'     => $this->confirmed,
          'received'      => $this->received,
        ];
    }
}
