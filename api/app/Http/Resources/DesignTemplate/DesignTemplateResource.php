<?php

namespace App\Http\Resources\DesignTemplate;

use Illuminate\Http\Resources\Json\JsonResource;

class DesignTemplateResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
    public function toArray($request)
    {
        self::withoutWrapping();

        return [
        'id'                                => $this->id,
        'title'                             => $this->title,
        'subject'                           => $this->subject,
        'design'                            => $this->getFirstMediaUrl('design_template'),
        'html'                              => $this->getFirstMediaUrl('design_template_html'),
        'preview'                           => $this->getFirstMediaUrl('design_template_preview'),
        'created_at'                        => $this->created_at,
        'updated_at'                        => $this->updated_at,
        'deleted_at'                        => $this->deleted_at,
        ];
    }
}
