<?php

use App\Enums\SettingKey;

/**
 * This file configures demo mode functionality, controlling restricted actions and periodic database resets.
 *
 * Structure:
 * - `enabled`: Boolean (via DEMO_MODE env) to toggle demo mode globally.
 * - `messages`: Contains the `global` message, used as the fallback for all demo mode notifications.
 * - `database_reset_unit`: Time unit for database resets ('second', 'minute', 'hour', 'day', 'month', 'year').
 * - `database_reset_duration`: Integer duration for reset intervals (e.g., 4 for 4 hours).
 * - `feature`: Array of restricted features (e.g., 'settings', 'admin_profile'), each with:
 *   - `enabled`: Boolean to toggle feature restrictions.
 *   - `default`: Default message for the feature if no restriction-specific message exists.
 *   - `routes`: Array of route names to restrict for this feature.
 *   - `restrictions`: Optional array of restricted keys (e.g., 'site_settings' with sub-keys like 'site_name').
 *   - `messages`: Optional array of restriction-specific messages (e.g., 'site_settings' => 'Custom message').
 *
 * Messaging Hierarchy:
 * - Restriction-specific message (feature.messages[key]) is used if defined.
 * - Feature default message (feature.default) is used if no restriction-specific message exists.
 * - Global message (messages.global) is used as the final fallback.
 *
 * Database Reset:
 * - If `enabled` is true, the database is reset using the SQL file at `storage_path('../resources/database/database.sql')`.
 * - Resets occur based on `database_reset_unit` and `database_reset_duration` (e.g., every 4 hours).
 * - Last reset time is tracked in `storage/demo_reset.json`.
 *
 * Usage:
 * - Routes in each feature's `routes` array are restricted in demo mode via the RestrictDemoMode middleware.
 * - Features with `enabled => true` and no `restrictions` block all updates on the specified routes.
 * - Features with `restrictions` filter specified keys, allowing non-restricted keys to proceed.
 */

return [
    
    'enabled' => env('APP_MODE', 'live') == "demo",
    'messages' => [
        'global' => 'This is a demo environment. Some actions are restricted.',
    ],
    
    'database_reset_unit' => 'second',
    'database_reset_duration' => 4,

    'feature' => [
        'settings' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.system.setting.store',
            ],
            'restrictions' => [
                'site_settings' => [
                    'site_name',
                    'copyright',
                    'phone',
                    'email',
                    'address',
                    'app_link',
                    'primary_color',
                    'primary_text_color',
                    'secondary_color',
                    'trinary_color',
                    'site_logo',
                    'site_square_logo',
                    'panel_logo',
                    'favicon',
                    'meta_image',
                    'time_zone',
                    'country_code',
                    'debug_mode',
                    'landing_page',
                    'maintenance_mode',
                    'queue_connection_config',
                    'max_file_size',
                    'max_file_upload',
                    'mime_types',
                    'auth_heading',
                    'authentication_background',
                    'authentication_background_inner_image_one',
                    'authentication_background_inner_image_two',
                    'member_authentication' => [
                        'registration',
                        'login',
                    ],
                    'available_plugins',
                    'meta_title',
                    'meta_keywords',
                    'meta_description',
                    'android_off_canvas_guide',
                    'whatsapp_off_canvas_guide',
                ],
            ],
            'messages' => [],
        ],
        'admin_profile' => [
            'enabled' => true,
            'default' => 'Admin Profile update is restricted in demo mode.',
            'routes' => [
                'admin.profile.update',
            ],
        ],
        'admin_password' => [
            'enabled' => true,
            'default' => 'Admin Password update is restricted in demo mode.',
            'routes' => [
                'admin.password.update',
            ],
        ],
        'admin_password_reset' => [
            'enabled' => true,
            'default' => 'Admin Password reset is restricted in demo mode.',
            'routes' => [
                'admin.password.reset.update',
            ],
        ],
        'user_profile' => [
            'enabled' => true,
            'default' => 'User Profile update is restricted in demo mode.',
            'routes' => [
                'user.profile.update',
            ],
        ],
        'user_password' => [
            'enabled' => true,
            'default' => 'User Password update is restricted in demo mode.',
            'routes' => [
                'user.password.update',
            ],
        ],
        'admin_membership_plan' => [
            'enabled' => true,
            'default' => 'You cannot update or delete membership plans in demo mode.',
            'routes' => [
                'admin.membership.plan.update',
                'admin.membership.plan.delete',
                'admin.membership.plan.status.update',
                'admin.membership.plan.bulk',
            ],
        ],
        'admin_template' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.template.update',
            ],
        ],
        'admin_customer' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.user.update',
            ],
        ],
        'frontend_section' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.frontend.sections.save.content',
            ],
        ],
        'admin_blogs' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.blog.create',
                'admin.blog.save',
                'admin.blog.delete',
                'admin.blog.status.update',
                'admin.blog.bulk',
            ],
        ],
        'whatsapp_server' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.gateway.whatsapp.device.server.update',
            ],
        ],
        'install_update' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.system.install.update',
            ],
        ],
        'system_update' => [
            'enabled' => true,
            'default' => 'Demo Mode restrictions.',
            'routes' => [
                'admin.system.update',
            ],
        ],
    ],
];