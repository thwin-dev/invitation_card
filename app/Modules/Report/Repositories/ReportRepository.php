<?php

namespace App\Modules\Report\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    public function enrollments(array $filters): LengthAwarePaginator
    {
        return $this->dateRange(
            DB::table('student_course_accesses as accesses')
                ->join('users', 'users.id', '=', 'accesses.user_id')
                ->join('courses', 'courses.id', '=', 'accesses.course_id')
                ->select('accesses.id', 'accesses.user_id as student_id', 'users.name as student_name', 'users.email', 'accesses.course_id', 'courses.title as course_title', 'accesses.status', 'accesses.source', 'accesses.granted_at', 'accesses.created_at'),
            'accesses.created_at',
            $filters,
        )->when($filters['status'] ?? null, fn ($q, $v) => $q->where('accesses.status', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('accesses.course_id', $v))
            ->latest('accesses.created_at')
            ->paginate($this->perPage($filters));
    }

    public function revenue(array $filters): LengthAwarePaginator
    {
        return $this->dateRange(
            DB::table('payments')
                ->join('users', 'users.id', '=', 'payments.user_id')
                ->join('courses', 'courses.id', '=', 'payments.course_id')
                ->join('payment_channels', 'payment_channels.id', '=', 'payments.payment_channel_id')
                ->select('payments.id', 'payments.transaction_id', 'payments.user_id as student_id', 'users.name as student_name', 'payments.course_id', 'courses.title as course_title', 'payments.payment_channel_id', 'payment_channels.name as payment_channel', 'payments.amount_mmk', 'payments.status', 'payments.submitted_at'),
            'payments.submitted_at',
            $filters,
        )->when($filters['status'] ?? null, fn ($q, $v) => $q->where('payments.status', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('payments.course_id', $v))
            ->latest('payments.submitted_at')
            ->paginate($this->perPage($filters));
    }

    public function completion(array $filters): LengthAwarePaginator
    {
        $query = DB::table('courses')
            ->leftJoin('course_modules', 'course_modules.course_id', '=', 'courses.id')
            ->leftJoin('lessons', 'lessons.course_module_id', '=', 'course_modules.id')
            ->select('courses.id as course_id', 'courses.title as course_title', 'courses.category')
            ->selectRaw('COUNT(DISTINCT lessons.id) as total_lessons')
            ->selectRaw('(SELECT COUNT(DISTINCT CONCAT(lp.user_id, "-", lp.lesson_id)) FROM lesson_progresses lp WHERE lp.course_id = courses.id AND lp.completed_at IS NOT NULL) as completed_lesson_records')
            ->groupBy('courses.id', 'courses.title', 'courses.category');

        return $query->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('courses.id', $v))
            ->orderBy('courses.title')
            ->paginate($this->perPage($filters));
    }

    public function studentProgress(array $filters): LengthAwarePaginator
    {
        $query = DB::table('student_course_accesses as accesses')
            ->join('users', 'users.id', '=', 'accesses.user_id')
            ->join('courses', 'courses.id', '=', 'accesses.course_id')
            ->leftJoin('course_modules', 'course_modules.course_id', '=', 'courses.id')
            ->leftJoin('lessons', 'lessons.course_module_id', '=', 'course_modules.id')
            ->select('accesses.user_id as student_id', 'users.name as student_name', 'users.email', 'accesses.course_id', 'courses.title as course_title', 'accesses.status as enrollment_status')
            ->selectRaw('COUNT(DISTINCT lessons.id) as total_lessons')
            ->selectRaw('COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM lesson_progresses lp WHERE lp.user_id = accesses.user_id AND lp.lesson_id = lessons.id AND lp.completed_at IS NOT NULL) THEN lessons.id END) as completed_lessons')
            ->selectRaw('(SELECT MAX(lp2.last_watched_at) FROM lesson_progresses lp2 WHERE lp2.user_id = accesses.user_id AND lp2.course_id = accesses.course_id) as last_activity_at')
            ->groupBy('accesses.user_id', 'users.name', 'users.email', 'accesses.course_id', 'courses.title', 'accesses.status');

        return $query->when($filters['student_id'] ?? null, fn ($q, $v) => $q->where('accesses.user_id', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('accesses.course_id', $v))
            ->when($filters['student_status'] ?? null, fn ($q, $v) => $q->where('users.status', $v))
            ->orderBy('users.name')
            ->paginate($this->perPage($filters));
    }

    public function certificates(array $filters): LengthAwarePaginator
    {
        return $this->dateRange(
            DB::table('certificates')
                ->join('users', 'users.id', '=', 'certificates.user_id')
                ->join('courses', 'courses.id', '=', 'certificates.course_id')
                ->select('certificates.certificate_id', 'certificates.user_id as student_id', 'users.name as student_name', 'users.email', 'certificates.course_id', 'courses.title as course_title', 'certificates.status', 'certificates.issued_at', 'certificates.revoked_at'),
            'certificates.issued_at',
            $filters,
        )->when($filters['status'] ?? null, fn ($q, $v) => $q->where('certificates.status', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('certificates.course_id', $v))
            ->latest('certificates.issued_at')
            ->paginate($this->perPage($filters));
    }

    public function trainers(array $filters): LengthAwarePaginator
    {
        $query = DB::table('trainers')
            ->join('course_trainer', 'course_trainer.trainer_id', '=', 'trainers.id')
            ->join('courses', 'courses.id', '=', 'course_trainer.course_id')
            ->leftJoin('student_course_accesses as accesses', 'accesses.course_id', '=', 'courses.id')
            ->select('trainers.id as trainer_id', 'trainers.name as trainer_name', 'courses.id as course_id', 'courses.title as course_title')
            ->selectRaw('COUNT(DISTINCT CASE WHEN accesses.status = "active" THEN accesses.id END) as active_enrollments')
            ->selectRaw('COUNT(DISTINCT accesses.user_id) as enrolled_students')
            ->groupBy('trainers.id', 'trainers.name', 'courses.id', 'courses.title');

        return $query->when($filters['trainer_id'] ?? null, fn ($q, $v) => $q->where('trainers.id', $v))
            ->when($filters['course_id'] ?? null, fn ($q, $v) => $q->where('courses.id', $v))
            ->orderBy('trainers.name')->orderBy('courses.title')
            ->paginate($this->perPage($filters));
    }

    private function dateRange(Builder $query, string $column, array $filters): Builder
    {
        return $query->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate($column, '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate($column, '<=', $v));
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 1), 100);
    }
}
