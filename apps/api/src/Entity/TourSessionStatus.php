<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum TourSessionStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case INCOMPLETE = 'incomplete';
    case MISSED = 'missed';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
