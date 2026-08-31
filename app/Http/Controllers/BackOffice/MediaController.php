<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaQuickRequest;
use App\Services\BackOffice\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Request $request): InertiaResponse
    {
        $medias = $this->mediaService->search($request);

        return Inertia::render('back-office/medias/Index', [
            'medias' => $medias,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $media = $this->mediaService->find($slug);
        return Inertia::render('back-office/medias/Details', [
            'media' => $media,
        ]);
    }

    public function quickSave(MediaQuickRequest $request): array
    {
        return $this->mediaService->quickSave($request);
    }

    public function quickUpdate(MediaQuickRequest $request, string $slug): array
    {
        $media = $this->mediaService->find($slug);
        return $this->mediaService->quickUpdate($request, $media);
    }

    public function delete(string $slug): RedirectResponse
    {
        $media = $this->mediaService->find($slug);

        $result = $this->mediaService->delete($media);

        return to_route('back-office.medias.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
