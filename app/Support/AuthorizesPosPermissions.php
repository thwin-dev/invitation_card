<?php

namespace App\Support;

trait AuthorizesPosPermissions
{
    protected function authorizePosPermission(string $module, string $action): void
    {
        abort_unless(auth('web')->user()?->hasPermissionTo($module, $action), 403);
    }
}
