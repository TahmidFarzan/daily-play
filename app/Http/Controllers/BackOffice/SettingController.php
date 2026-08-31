<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Services\BackOffice\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index(): InertiaResponse
    {
        return Inertia::render('back-office/settings/Index');
    }

    public function robotsTxtEdit(): InertiaResponse
    {
        $robotsTxt = $this->settingService->getRobotsTxt();

        return Inertia::render('back-office/settings/RobotsTxtEdit', [
            'robotsTxt' => $robotsTxt,
        ]);
    }

    public function adsTxtEdit(): InertiaResponse
    {
        $adsTxt = $this->settingService->getAdsTxt();

        return Inertia::render('back-office/settings/AdsTxtEdit', [
            'adsTxt' => $adsTxt,
        ]);
    }

    public function queueStart(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueStart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueRestart(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueRestart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueClear(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueClear();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueFlush(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueFlush();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueMonitorStale(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueMonitorStale();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueMonitorPurge(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueMonitorPurge();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function scheduleStart(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->scheduleStart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function scheduleStop(): RedirectResponse
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->scheduleStop();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function robotsTxtSave(Request $request): RedirectResponse
    {
        $result = $this->settingService->saveRobotsTxt($request);

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function adsTxtSave(Request $request): RedirectResponse
    {
        $result = $this->settingService->saveAdsTxt($request);

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }
}
