<?php

namespace App\Support;

class PermissionCatalog
{
    public const ACTIONS = ['view', 'create', 'edit', 'delete', 'import', 'export', 'restore'];

    /** @return array<string, string> */
    public static function modules(): array
    {
        $modules = [];

        foreach (self::configuration() as $module => $settings) {
            if (! is_string($module) || ! is_array($settings) || ! is_string($settings['name'] ?? null)) {
                continue;
            }

            $modules[$module] = $settings['name'];
        }

        return $modules;
    }

    /** @return list<array{module: string, module_label: string, action: string, key: string, label: string}> */
    public static function entries(): array
    {
        $entries = [];

        foreach (self::configuration() as $module => $settings) {
            if (! is_string($module) || ! is_array($settings) || ! is_string($settings['name'] ?? null)) {
                continue;
            }

            $moduleLabel = $settings['name'];
            $permissions = is_array($settings['permissions'] ?? null) ? $settings['permissions'] : [];

            foreach (self::ACTIONS as $action) {
                if (($permissions[$action] ?? false) !== true) {
                    continue;
                }

                $entries[] = [
                    'module' => $module,
                    'module_label' => $moduleLabel,
                    'action' => $action,
                    'key' => self::key($module, $action),
                    'label' => ucfirst($action),
                ];
            }
        }

        return $entries;
    }

    public static function key(string $module, string $action): string
    {
        return $module.'.'.$action;
    }

    public static function contains(string $module, string $action): bool
    {
        if (! in_array($action, self::ACTIONS, true)) {
            return false;
        }

        $permissions = self::configuration()[$module]['permissions'] ?? null;

        return is_array($permissions) && ($permissions[$action] ?? false) === true;
    }

    /** @return array<string, array{name?: string, permissions?: array<string, bool>}> */
    private static function configuration(): array
    {
        $configuration = config('permission_list', []);

        return is_array($configuration) ? $configuration : [];
    }
}
