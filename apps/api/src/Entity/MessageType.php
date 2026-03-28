<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case VOICE = 'voice';
    case FILE = 'file';
    case LOCATION = 'location';
    public function label(): string { return ucfirst($this->value); }
}
