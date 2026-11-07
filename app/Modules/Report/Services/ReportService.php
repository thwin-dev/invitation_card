<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Repositories\ReportRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function __construct(private readonly ReportRepository $reports) {}

    public function enrollments(array $filters): LengthAwarePaginator { return $this->reports->enrollments($filters); }
    public function revenue(array $filters): LengthAwarePaginator { return $this->reports->revenue($filters); }
    public function completion(array $filters): LengthAwarePaginator { return $this->reports->completion($filters); }
    public function studentProgress(array $filters): LengthAwarePaginator { return $this->reports->studentProgress($filters); }
    public function certificates(array $filters): LengthAwarePaginator { return $this->reports->certificates($filters); }
    public function trainers(array $filters): LengthAwarePaginator { return $this->reports->trainers($filters); }
}
