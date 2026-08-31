<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @if (config('queue-monitor.ui.refresh_interval'))
            <meta http-equiv="refresh" content="{{ config('queue-monitor.ui.refresh_interval') }}">
        @endif

        <title>@lang('queue-monitor.queue_monitor')</title>

        <link href="{{ config('app.app_favicon') }}" rel="icon" loading="lazy">

        @vite('resources/css/app.css')
    </head>

    <body>
        <div class="flex flex-col min-h-screen">
            <header class="top-0 left-0 w-full bg-white shadow-sm z-50">
                <div class="px-4 py-2 flex items-center justify-between">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                        <span class="font-semibold truncate">
                            {{ config('app.name') }}
                        </span>
                    </a>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('auth-user.dashboard.index') }}" class="items-center gap-2 min-w-0">
                            Dashboard
                        </a>

                        @if (config('queue-monitor.ui.show_metrics'))
                            <div class="px-4 text-sm font-light">
                                @lang('queue-monitor.statistics')
                            </div>
                        @endif
                    </div>
                </div>
            </header>

            <main class="px-4 py-2">
                <div class="p-4 border border-slate-800 rounded-2xl mb-2">
                    @if (config('queue-monitor.ui.show_metrics'))
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($metrics->all() as $metric)
                                @include('queue-monitor::partials.metrics-card', [
                                    'metric' => $metric,
                                ])
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-4 border border-slate-800 rounded-2xl mb-2">
                    <h2 class="mb-4 text-gray-800 text-sm font-medium">
                        @lang('queue-monitor.filter')
                    </h2>

                    @include('queue-monitor::partials.filter', [
                        'filters' => $filters,
                    ])
                </div>

                <div class="p-4 border border-slate-800 rounded-2xl">
                    <h2 class="mb-4 text-gray-800 text-sm font-medium">
                        @lang('queue-monitor.jobs')
                    </h2>

                    @include('queue-monitor::partials.table', [
                        'jobs' => $jobs,
                    ])

                    @if (config('queue-monitor.ui.allow_purge'))
                        <div class="mt-12">
                            <form action="{{ route('queue-monitor::purge') }}" method="post">
                                @csrf
                                @method('delete')

                                <button
                                    class="py-2 px-4 bg-red-50 dark:bg-red-200 hover:dark:bg-red-300 hover:bg-red-100 text-red-800 text-xs font-medium rounded-md transition-colors duration-150">
                                    @lang('queue-monitor.delete_all_entries')
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </main>
        </div>


    </body>

</html>
