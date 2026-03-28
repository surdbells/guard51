<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum VisitorStatus: string
{
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
