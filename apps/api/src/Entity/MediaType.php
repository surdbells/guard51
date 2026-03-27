<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum MediaType: string
{
    case PHOTO = 'photo';
    case VIDEO = 'video';

    public function label(): string { return ucfirst($this->value); }
}
