<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class ArtisanCommandHelper
{
    public static function configClearCMD()
    {
        Artisan::call('config:clear');
    }

    public static function configClearCache()
    {
        Artisan::call('config:cache');
    }

    public static function queueWorkCheckAndStartCMD()
    {
        $process = new Process(['pgrep', '-f', 'queue:work']);
        $process->run();
        if (! $process->isSuccessful()) {
            ArtisanCommandHelper::queueWorkStartCMD();
        }
    }

    public static function queueWorkStartCMD()
    {
        Artisan::call('queue:work');
    }

    public static function queueWorkReStartCMD()
    {
        Artisan::call('queue:restart');
    }

    public static function queueClearCMD()
    {
        Artisan::call('queue:clear');
    }

    public static function queueFlushCMD()
    {
        Artisan::call('queue:flush');
    }

    public static function queueMonitorPurgeCMD()
    {
        Artisan::call('queue-monitor:purge');
    }

    public static function queueMonitorStaleCMD()
    {
        Artisan::call('queue-monitor:stale');
    }

    public static function scheduleWorkCMD()
    {
        Artisan::call('schedule:work');
    }

    public static function scheduleInterruptCMD()
    {
        Artisan::call('schedule:interrupt');
    }
}
