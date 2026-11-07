<?php

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Course\Resources\PortalCourseResource;
use App\Modules\Course\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortalCourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $student = $request->user() instanceof User ? $request->user() : null;

        return PortalCourseResource::collection($this->courses->listForPortal($request->only([
            'search',
            'category',
            'level',
            'price_type',
            'per_page',
        ]), $student));
    }

    public function featured(Request $request): AnonymousResourceCollection
    {
        $student = $request->user() instanceof User ? $request->user() : null;

        return PortalCourseResource::collection($this->courses->listFeaturedForPortal($request->only([
            'per_page',
        ]), $student));
    }

    public function show(Request $request, int $course): PortalCourseResource
    {
        $student = $request->user() instanceof User ? $request->user() : null;

        return PortalCourseResource::make($this->courses->findPublishedForPortal($course, $student));
    }
}
