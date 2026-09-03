<?php

namespace App\Modules\Portal\Enums;

/** Category-scoped visibility (04-client-portal §5 document security). */
enum DocumentVisibility: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Both = 'both';

    /**
     * Can a portal member with this org role see the document?
     *
     * The matrix matches documentsFor()'s query exactly: employee docs
     * stay employee-eyes-only (managers have the org board for
     * oversight — not the assignee's personal paperwork), manager docs
     * stay above employee eyes, billing rides the manager lane.
     */
    public function visibleTo(string $roleInOrg): bool
    {
        return match ($this) {
            self::Employee => $roleInOrg === 'employee',
            self::Manager => $roleInOrg === 'manager' || $roleInOrg === 'billing',
            self::Both => true,
        };
    }
}
