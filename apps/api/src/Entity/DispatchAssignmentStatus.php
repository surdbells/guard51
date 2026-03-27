<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum DispatchAssignmentStatus: string
{
    case ASSIGNED = 'assigned';
    case ACKNOWLEDGED = 'acknowledged';
    case EN_ROUTE = 'en_route';
    case ON_SCENE = 'on_scene';
    case COMPLETED = 'completed';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
