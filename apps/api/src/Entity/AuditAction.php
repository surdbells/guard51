<?php
declare(strict_types=1);
namespace Guard51\Entity;

enum AuditAction: string
{
    case LOGIN = 'login'; case LOGOUT = 'logout'; case LOGIN_FAILED = 'login_failed';
    case CREATE = 'create'; case UPDATE = 'update'; case DELETE = 'delete';
    case APPROVE = 'approve'; case REJECT = 'reject'; case ESCALATE = 'escalate';
    case EXPORT = 'export'; case IMPORT = 'import';
    case ENABLE_2FA = 'enable_2fa'; case DISABLE_2FA = 'disable_2fa';
    case PERMISSION_CHANGE = 'permission_change'; case SETTINGS_CHANGE = 'settings_change';
    public function label(): string { return ucfirst(str_replace('_', ' ', $this->value)); }
}
