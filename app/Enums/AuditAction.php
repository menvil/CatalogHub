<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'security.login';
    case Logout = 'security.logout';
    case RoleAssigned = 'security.role.assigned';
    case MembershipChanged = 'security.membership.changed';
    case UserDisabled = 'security.user.disabled';
    case UserEnabled = 'security.user.enabled';
}
