@php
    use romanzipp\QueueMonitor\Enums\MonitorStatus;
@endphp

<div class="w-full overflow-x-auto rounded-2xl border border-slate-800">

    <table class="min-w-[1100px] w-full border-collapse text-sm">

        <thead>
            <tr>
                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.status')
                </th>

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.job')
                </th>

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.details')
                </th>

                @if (config('queue-monitor.ui.show_custom_data'))
                    <th
                        class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                        @lang('queue-monitor.custom_data')
                    </th>
                @endif

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.progress')
                </th>

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.duration')
                </th>

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.started')
                </th>

                <th
                    class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400 border-b border-slate-800">
                    @lang('queue-monitor.error')
                </th>

                @if (config('queue-monitor.ui.allow_deletion') || config('queue-monitor.ui.allow_retry'))
                    <th class="px-3 py-2 border-b border-slate-800"></th>
                @endif
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-800">

            @forelse($jobs as $job)
                <tr class="hover:bg-slate-900/70 transition">

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        @include('queue-monitor::partials.job-status', ['status' => $job->status])
                    </td>

                    <td class="px-3 py-2 text-xs text-slate-300 align-top font-medium">
                        <div class="text-slate-100">
                            {{ $job->getBaseName() }}
                        </div>

                        <div class="mt-1 text-[11px] text-slate-500">
                            #{{ $job->job_id }}
                        </div>
                    </td>

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        <div>
                            <span class="text-slate-500">@lang('queue-monitor.queue'):</span>
                            <span class="font-semibold text-slate-200">{{ $job->queue }}</span>
                        </div>

                        <div class="mt-1">
                            <span class="text-slate-500">@lang('queue-monitor.attempt'):</span>
                            <span class="font-semibold text-slate-200">{{ $job->attempt }}</span>
                        </div>

                        @if ($job->retried)
                            <div class="mt-2">
                                <span class="rounded bg-slate-800 px-2 py-1 text-[11px] font-medium text-slate-300">
                                    @lang('queue-monitor.retried')
                                </span>
                            </div>
                        @endif
                    </td>

                    @if (config('queue-monitor.ui.show_custom_data'))
                        <td class="px-3 py-2 text-xs text-slate-300 align-top">
                            <textarea rows="3" class="w-64 rounded-lg border border-slate-700 bg-slate-900 p-2 text-[11px] text-slate-300"
                                readonly>{{ json_encode($job->getData(), JSON_PRETTY_PRINT) }}</textarea>
                        </td>
                    @endif

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        @if ($job->progress !== null)
                            <div class="w-28">
                                <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full rounded-full bg-emerald-500"
                                        style="width: {{ $job->progress }}%"></div>
                                </div>

                                <div class="mt-1 text-center text-[11px] font-semibold text-slate-300">
                                    {{ $job->progress }}%
                                </div>
                            </div>
                        @else
                            <span class="text-slate-500">-</span>
                        @endif
                    </td>

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        {{ $job->getElapsedInterval()->format('%H:%I:%S') }}
                    </td>

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        {{ $job->started_at?->diffForHumans() }}
                    </td>

                    <td class="px-3 py-2 text-xs text-slate-300 align-top">
                        @if ($job->status != MonitorStatus::SUCCEEDED && $job->exception_message !== null)
                            <textarea rows="3" class="w-64 rounded-lg border border-red-900/50 bg-red-950/30 p-2 text-[11px] text-red-300"
                                readonly>{{ $job->exception_message }}</textarea>
                        @else
                            <span class="text-slate-500">-</span>
                        @endif
                    </td>

                    @if (config('queue-monitor.ui.allow_deletion') || config('queue-monitor.ui.allow_retry'))
                        <td class="px-3 py-2 text-xs align-top">
                            <div class="flex gap-2">
                                @if (config('queue-monitor.ui.allow_retry') && $job->canBeRetried())
                                    <form action="{{ route('queue-monitor::retry', [$job]) }}" method="post">
                                        @csrf
                                        @method('patch')

                                        <button
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-500">
                                            @lang('queue-monitor.retry')
                                        </button>
                                    </form>
                                @endif

                                @if (config('queue-monitor.ui.allow_deletion') && $job->isFinished())
                                    <form action="{{ route('queue-monitor::destroy', [$job]) }}" method="post">
                                        @csrf
                                        @method('delete')

                                        <button
                                            class="rounded-lg bg-red-500/10 px-3 py-1.5 text-[11px] font-semibold text-red-400 hover:bg-red-500/20">
                                            @lang('queue-monitor.delete')
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif

                </tr>
            @empty
                <tr>
                    <td colspan="100" class="px-3 py-10 text-center text-sm text-slate-500">
                        @lang('queue-monitor.no_jobs')
                    </td>
                </tr>
            @endforelse

        </tbody>

        <tfoot>
            <tr>
                <td colspan="100" class="px-3 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-xs">
                            @lang('queue-monitor.showing')

                            @if ($jobs->total() > 0)
                                <span class="font-semibold">{{ $jobs->firstItem() }}</span>
                                @lang('queue-monitor.to')
                                <span class="font-semibold">{{ $jobs->lastItem() }}</span>
                                @lang('queue-monitor.of')
                            @endif

                            <span class="font-semibold">{{ $jobs->total() }}</span>
                            {{ trans_choice('queue-monitor.results', $jobs->total()) }}
                        </div>

                        <div class="flex gap-2">
                            <a class="rounded-lg px-3 py-1.5 text-xs font-semibold
                                @if (!$jobs->onFirstPage()) text-white bg-slate-800 hover:bg-slate-700
                                @else
                                    cursor-not-allowed @endif rounded-2xl border border-slate-800"
                                @if (!$jobs->onFirstPage()) href="{{ $jobs->previousPageUrl() }}" @endif>
                                @lang('queue-monitor.previous')
                            </a>

                            <a class="rounded-lg px-3 py-1.5 text-xs  font-semibold
                                @if ($jobs->hasMorePages()) text-white bg-slate-800 hover:bg-slate-700
                                @else
                                    cursor-not-allowed @endif rounded-2xl border border-slate-800"
                                @if ($jobs->hasMorePages()) href="{{ $jobs->url($jobs->currentPage() + 1) }}" @endif>
                                @lang('queue-monitor.next')
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        </tfoot>

    </table>

</div>
