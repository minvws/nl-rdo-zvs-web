<?php

declare(strict_types=1);

namespace App\Enums\Authorization;

enum Permission: string
{
    /**
     * Admin panel permissions
     */
    case ADMIN_PANEL_VIEW = 'admin.panel.view';

    /**
     * User permissions
     */
    case USER_WRITE = 'user.write';
    case USER_READ = 'user.read';

    /**
     * Petition permissions
     */
    case PETITION_NUMBER_OVERRULE = 'petition.number.overrule';
    case PETITION_WRITE = 'petition.write';
    case PETITION_READ = 'petition.read';

    /**
     * Holiday permissions
     */
    case PUBLIC_HOLIDAY_WRITE = 'public.holiday.write';
    case PUBLIC_HOLIDAY_READ = 'public.holiday.read';

    /**
     * Policy Department permissions
     */
    case POLICY_DEPARTMENT_WRITE = 'policy.department.write';
    case POLICY_DEPARTMENT_READ = 'policy.department.read';

    /**
     * Petition Type permissions
     */
    case PETITION_TYPE_WRITE = 'petition.type.write';
    case PETITION_TYPE_READ = 'petition.type.read';

    /**
     * Contact permissions
     */
    case CONTACT_MANAGE = 'contact.manage';
    case CONTACT_WRITE = 'contact.write';
    case CONTACT_READ = 'contact.read';

    /**
     * Department permissions
     */
    case DEPARTMENT_READ = 'department.read';

    /**
     * Decision permissions
     */
    case DECISION_WRITE = 'decision.write';
    case DECISION_READ = 'decision.read';

    /**
     * Category permissions
     */
    case PETITION_CATEGORY_WRITE = 'petition_category.write';
    case PETITION_CATEGORY_READ = 'petition_category.read';
}
