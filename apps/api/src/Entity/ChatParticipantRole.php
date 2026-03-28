<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ChatParticipantRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
    public function label(): string { return ucfirst($this->value); }
}
