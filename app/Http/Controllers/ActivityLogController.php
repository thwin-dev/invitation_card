<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $this->filteredLogs($request)
            ->with('actor')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('management.security.activity-logs', [
            'logs' => $logs,
            'users' => User::query()->where(function (Builder $query): void {
                $query->whereNull('role')->orWhereNotIn('role', ['Customer', 'customer']);
            })->orderBy('name')->get(['id', 'name', 'user_code']),
            'modules' => PermissionCatalog::modules(),
            'actions' => ActivityLogger::ACTIONS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $logs = $this->filteredLogs($request);
        $lastExistingId = (int) (ActivityLog::query()->max('id') ?? 0);
        $moduleLabels = PermissionCatalog::modules();
        ActivityLogger::record('activity-logs', 'exported', 'Exported activity and audit logs');

        $logs->where('id', '<=', $lastExistingId)
            ->with('actor')
            ->orderBy('id');

        return response()->streamDownload(function () use ($logs, $moduleLabels): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'Date and time', 'Actor', 'User code', 'Module', 'Action', 'Description',
                'Subject type', 'Subject ID', 'Route', 'HTTP method', 'IP address', 'Changes',
            ], ',', '"', '');

            foreach ($logs->lazyById(500) as $log) {
                $row = [
                    $log->created_at?->toDateTimeString(),
                    $log->actor_name ?? $log->actor?->name,
                    $log->actor_user_code ?? $log->actor?->user_code,
                    $moduleLabels[$log->module] ?? $log->module,
                    Str::headline($log->action),
                    $log->description,
                    $log->subject_type ? class_basename($log->subject_type) : '',
                    $log->subject_id,
                    $log->route_name,
                    $log->http_method,
                    $log->ip_address,
                    json_encode($log->changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                fputcsv($output, array_map(fn (mixed $value): string => self::safeCsvCell($value), $row), ',', '"', '');
            }

            fclose($output);
        }, 'activity-audit-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredLogs(Request $request): Builder
    {
        $filters = $request->validate([
            'module' => ['nullable', 'string', Rule::in(array_keys(PermissionCatalog::modules()))],
            'action' => ['nullable', 'string', Rule::in(ActivityLogger::ACTIONS)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return ActivityLog::query()
            ->when($filters['module'] ?? null, fn (Builder $query, string $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('actor_id', $userId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function (Builder $query) use ($filters): void {
                $search = '%'.trim($filters['search']).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', $search)
                        ->orWhere('actor_name', 'like', $search)
                        ->orWhere('actor_user_code', 'like', $search)
                        ->orWhere('subject_type', 'like', $search)
                        ->orWhere('subject_id', 'like', $search)
                        ->orWhere('route_name', 'like', $search);
                });
            });
    }

    private static function safeCsvCell(mixed $value): string
    {
        $value = $value === null ? '' : (string) $value;

        return preg_match('/^[\x00-\x20\xEF\xBB\xBF]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }
}
