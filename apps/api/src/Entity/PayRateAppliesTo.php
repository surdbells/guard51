<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum PayRateAppliesTo: string
{
    case OVERTIME = 'overtime';
    case HOLIDAY = 'holiday';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';

    public function label(): string { return ucfirst($this->value); }
}
