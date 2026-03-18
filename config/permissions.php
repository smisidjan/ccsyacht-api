<?php

return [

    /*
    |--------------------------------------------------------------------------
    | All Available Permissions
    |--------------------------------------------------------------------------
    |
    | Complete list of all permissions in the system.
    | Used for tenant creation without requiring database access.
    |
    */

    'all' => [
        // Users
        'view_users',
        'edit_users',
        'delete_users',

        // Invitations
        'view_invitations',
        'create_invitations',
        'manage_invitations',

        // Registration Requests
        'view_registrations',
        'process_registrations',

        // Organization Settings
        'manage_guest_roles',
        'manage_settings',

        // Shipyards
        'view_shipyards',
        'create_shipyards',
        'edit_shipyards',
        'delete_shipyards',

        // Projects
        'view_projects',
        'create_projects',
        'edit_projects',
        'delete_projects',
        'view_project_members',
        'manage_project_members',
        'view_project_signers',
        'manage_project_signers',

        // Document Types
        'view_document_types',
        'create_document_types',
        'edit_document_types',
        'delete_document_types',

        // Documents
        'view_documents',
        'download_documents',
        'upload_documents',
        'delete_documents',

        // Decks
        'view_decks',
        'create_decks',
        'edit_decks',
        'delete_decks',

        // Areas
        'view_areas',
        'create_areas',
        'edit_areas',
        'delete_areas',

        // Stages
        'view_stages',
        'create_stages',
        'edit_stages',
        'delete_stages',

        // Logbook
        'view_logbook',
    ],

    /*
    |--------------------------------------------------------------------------
    | Always Restricted Permissions
    |--------------------------------------------------------------------------
    |
    | These permissions are ALWAYS restricted for non-master tenants.
    | They will be:
    | - Automatically added to restricted_permissions when creating a tenant
    | - Filtered out from the available permissions list
    | - Not selectable in the "Restricted Permissions" UI
    |
    */

    'always_restricted' => [
        'manage_guest_roles',
        'manage_settings',
    ],

];
