<?php

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Requests\StoreCourseRequest;
use App\Modules\Course\Requests\UpdateCourseRequest;
use App\Modules\Course\Resources\CourseResource;
use App\Modules\Course\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CourseResource::collection($this->courses->listForManagement($request->only([
            'search',
            'status',
            'category',
            'level',
            'price_type',
            'per_page',
        ])));
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        return CourseResource::make($this->courses->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Course $course): CourseResource
    {
        return CourseResource::make($course->load(['trainers', 'includes', 'learningOutcomes']));
    }

    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        return CourseResource::make($this->courses->update($course, $request->validated()));
    }

    public function destroy(Course $course): CourseResource
    {
        return CourseResource::make($this->courses->archive($course));
    }

    public function toggleFeatured(Course $course): CourseResource
    {
        return CourseResource::make($this->courses->toggleFeatured($course));
    }

    public function preview(Course $course): CourseResource
    {
        return CourseResource::make($course->load(['trainers', 'includes', 'learningOutcomes']));
    }
}
