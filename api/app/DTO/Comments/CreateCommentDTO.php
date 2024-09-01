<?php

namespace App\DTO\Comments;

use Spatie\DataTransferObject\DataTransferObject;

class CreateCommentDTO extends DataTransferObject
{
    public string $content;
}
