<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case REVIEWED = 'reviewed';
    case APPROVED = 'approved';

    public function label(): string { return ucfirst($this->value); }
}
