<?php

namespace App\Modules\Course\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Course\Resources\CourseWishlistResource;
use App\Modules\Course\Services\CourseWishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PortalCourseWishlistController extends Controller
{
    public function __construct(
        private readonly CourseWishlistService $wishlists,
    ) {}

    public function store(Request $request, int $course): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();
        $wishlist = $this->wishlists->add($student, $course);

        return CourseWishlistResource::make($wishlist)
            ->response()
            ->setStatusCode($wishlist->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $student */
        $student = $request->user();
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);

        return CourseWishlistResource::collection($this->wishlists->list($student, $perPage));
    }

    public function destroy(Request $request, int $course): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        $this->wishlists->remove($student, $course);

        return response()->json([
            'message' => 'Course removed from wishlist.',
        ]);
    }
}
