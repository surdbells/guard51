<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ParkingIncidentStatus: string
{
    case REPORTED = 'reported';
    case RESOLVED = 'resolved';
    public function label(): string { return ucfirst($this->value); }
}
