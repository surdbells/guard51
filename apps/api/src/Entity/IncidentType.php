<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum IncidentType: string
{
    case THEFT = 'theft';
    case TRESPASS = 'trespass';
    case VANDALISM = 'vandalism';
    case ASSAULT = 'assault';
    case FIRE = 'fire';
    case MEDICAL = 'medical';
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    case EQUIPMENT_FAILURE = 'equipment_failure';
    case OTHER = 'other';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
