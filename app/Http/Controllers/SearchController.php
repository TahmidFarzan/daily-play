<?php
namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->middleware(['auth', 'verified'])->only(['user']);
    }

    public function perPages(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->perPages($request)
        );
    }

    public function genders(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->genders($request)
        );
    }

    public function religions(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->religions($request)
        );
    }

    public function maritalStatuses(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->maritalStatuses($request)
        );
    }

    public function activityLogEvents(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->activityLogEvents($request)
        );
    }

    public function activityLogSubjectTypes(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->activityLogSubjectTypes($request)
        );
    }

    public function users(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->users($request)
        );
    }

    public function userPermissions(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->userPermissions($request)
        );
    }

    public function userPermissionsByGroup(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->userPermissionsByGroup($request)
        );
    }

    public function medias(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->medias($request)
        );
    }

    public function userPermission(string | int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->userPermission($slugOrId)
        );
    }
}
