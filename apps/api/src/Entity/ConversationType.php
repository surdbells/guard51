<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ConversationType: string
{
    case DIRECT = 'direct';
    case GROUP = 'group';
    case SITE_CHANNEL = 'site_channel';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
