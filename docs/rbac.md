# RBAC catalog

Module → Feature → Permission. Roles are permission bundles. One user may hold multiple roles via `model_has_roles` and organization membership.

## Permissions

Documented in code: `App\Rbac\PermissionCatalog`.

Key modules: `auth`, `profile`, `challenges`, `solutions`, `workspaces`, `matching`, `deals`, `investor`, `meetings`, `programme`, `exhibition`, `awards`, `surveys`, `sponsorship`, `cms`, `users`, `roles`, `analytics`, `audit`, `integrations`, `feature_flags`, `exports`, `admin`, `comms`.

## Roles

guest, attendee, corporate, innovator, university, researcher, innovation_hub, mentor, investor, exhibitor, government, student, technical_committee, organizer, super_admin.

Technical Committee: screen, prioritise, publish, allocate challenges.  
Organizer: broad event ops except super-admin-only flags/integrations/roles.  
Super Admin: all permissions.
