<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum DispatchCallType: string
{
    case EMERGENCY = 'emergency';
    case ROUTINE = 'routine';
    case COMPLAINT = 'complaint';
    case INFORMATION = 'information';
    case PANIC_RESPONSE = 'panic_response';

    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
