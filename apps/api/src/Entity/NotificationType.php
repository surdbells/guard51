<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum NotificationType: string
{
    case SHIFT_ASSIGNED = 'shift_assigned';
    case SHIFT_CHANGE = 'shift_change';
    case CLOCK_REMINDER = 'clock_reminder';
    case INCIDENT = 'incident';
    case PANIC = 'panic';
    case DISPATCH = 'dispatch';
    case REPORT = 'report';
    case MESSAGE = 'message';
    case INVOICE = 'invoice';
    case SYSTEM = 'system';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
