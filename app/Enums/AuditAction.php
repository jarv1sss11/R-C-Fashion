<?php

namespace App\Enums;

/**
 * Every administrator action that gets an audit trail. A closed list (rather
 * than a free-text string) keeps action names typo-free and filterable —
 * mirrors InteractionType's role for the recommendation engine.
 */
enum AuditAction: string
{
    case UserSuspended = 'user_suspended';
    case UserActivated = 'user_activated';
    case UserRoleChanged = 'user_role_changed';

    case VendorApproved = 'vendor_approved';
    case VendorRejected = 'vendor_rejected';
    case VendorSuspended = 'vendor_suspended';
    case VendorRestored = 'vendor_restored';

    case ProductApproved = 'product_approved';
    case ProductRejected = 'product_rejected';
    case ProductHidden = 'product_hidden';
    case ProductArchived = 'product_archived';
    case ProductRestored = 'product_restored';

    case CategoryCreated = 'category_created';
    case CategoryUpdated = 'category_updated';
    case CategoryArchived = 'category_archived';
    case CategoryRestored = 'category_restored';

    case SettingsUpdated = 'settings_updated';

    public function label(): string
    {
        return match ($this) {
            self::UserSuspended => 'User Suspended',
            self::UserActivated => 'User Activated',
            self::UserRoleChanged => 'User Role Changed',
            self::VendorApproved => 'Vendor Approved',
            self::VendorRejected => 'Vendor Rejected',
            self::VendorSuspended => 'Vendor Suspended',
            self::VendorRestored => 'Vendor Restored',
            self::ProductApproved => 'Product Approved',
            self::ProductRejected => 'Product Rejected',
            self::ProductHidden => 'Product Hidden',
            self::ProductArchived => 'Product Archived',
            self::ProductRestored => 'Product Restored',
            self::CategoryCreated => 'Category Created',
            self::CategoryUpdated => 'Category Updated',
            self::CategoryArchived => 'Category Archived',
            self::CategoryRestored => 'Category Restored',
            self::SettingsUpdated => 'Settings Updated',
        };
    }
}
