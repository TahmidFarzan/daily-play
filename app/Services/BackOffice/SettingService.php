<?php

namespace App\Services\BackOffice;

use App\Helpers\ArtisanCommandHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class SettingService
{
    public function queueStart(): array
    {
        try {
            ArtisanCommandHelper::queueWorkStartCMD();

            return [
                'status' => 'success',
                'message' => 'Queue monitor started successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to start queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to start queue monitor. Please try again.',
            ];
        }
    }

    public function queueRestart(): array
    {
        try {
            ArtisanCommandHelper::queueWorkReStartCMD();

            return [
                'status' => 'success',
                'message' => 'Queue monitor restarted successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to restart queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to restart queue monitor. Please try again.',
            ];
        }
    }

    public function queueClear(): array
    {
        try {
            ArtisanCommandHelper::queueClearCMD();

            return [
                'status' => 'success',
                'message' => 'Queue cleared successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to clear queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to clear queue. Please try again.',
            ];
        }
    }

    public function queueFlush(): array
    {
        try {
            ArtisanCommandHelper::queueFlushCMD();

            return [
                'status' => 'success',
                'message' => 'Queue flushed successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to flush queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to flush queue. Please try again.',
            ];
        }
    }

    public function queueMonitorPurge(): array
    {
        try {
            ArtisanCommandHelper::queueMonitorPurgeCMD();

            return [
                'status' => 'success',
                'message' => 'Queue monitor purged successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to purge queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to purge queue monitor. Please try again.',
            ];
        }
    }

    public function queueMonitorStale(): array
    {
        try {
            ArtisanCommandHelper::queueMonitorStaleCMD();

            return [
                'status' => 'success',
                'message' => 'Queue monitor stale jobs cleared successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to stale queue monitor.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to clear stale queue monitor jobs. Please try again.',
            ];
        }
    }

    public function scheduleStart(): array
    {
        try {
            ArtisanCommandHelper::scheduleWorkCMD();

            return [
                'status' => 'success',
                'message' => 'Schedule started successfully.',
            ];
        } catch (Exception $ex) {
            Log::error('Failed to start schedule.', [
                'exception' => $ex->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to start schedule. Please try again.',
            ];
        }
    }

    public function scheduleStop(): array
    {
        try {
            ArtisanCommandHelper::scheduleInterruptCMD();

            return [
                'status' => 'success',
                'message' => 'Schedule stopped successfully.',
            ];
        } catch (Exception $ex) {
            Log::error('Failed to stop schedule.', [
                'exception' => $ex->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to stop schedule. Please try again.',
            ];
        }
    }

    public function saveAdsTxt(Request $request): array
    {
        try {
            $filePath = 'public/ads.txt';
            $directory = dirname(storage_path('app/' . $filePath));

            if (! File::isDirectory($directory)) {
                if (! File::makeDirectory($directory, 0755, true)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to create directory. Please check permissions.',
                    ];
                }
            }

            if (! File::isWritable($directory)) {
                return [
                    'status' => 'error',
                    'message' => 'Directory is not writable. Please check permissions.',
                ];
            }

            if ($request->filled('ads_txt')) {
                Storage::put($filePath, $request->input('ads_txt'));
            }

            return [
                'status' => 'success',
                'message' => 'ads.txt saved successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to save ads.txt.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to save ads.txt. Please try again.',
            ];
        }
    }

    public function saveRobotsTxt(Request $request): array
    {
        try {
            $filePath = 'public/robots.txt';
            $directory = dirname(storage_path('app/' . $filePath));

            if (! File::isDirectory($directory)) {
                if (! File::makeDirectory($directory, 0755, true)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to create directory. Please check permissions.',
                    ];
                }
            }

            if (! File::isWritable($directory)) {
                return [
                    'status' => 'error',
                    'message' => 'Directory is not writable. Please check permissions.',
                ];
            }

            if ($request->filled('robots_txt')) {
                Storage::put($filePath, $request->input('robots_txt'));
            }

            return [
                'status' => 'success',
                'message' => 'robots.txt saved successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Failed to save robots.txt.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to save robots.txt. Please try again.',
            ];
        }
    }

    public function getAdsTxt(): string
    {
        return Storage::exists('public/ads.txt')
            ? Storage::get('public/ads.txt')
            : '';
    }

    public function getRobotsTxt(): string
    {
        return Storage::exists('public/robots.txt')
            ? Storage::get('public/robots.txt')
            : '';
    }
}
