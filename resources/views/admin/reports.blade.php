{{-- Administration — signalements. Version fonctionnelle minimale (backend). --}}
@php($reportController = \App\Http\Controllers\Admin\ReportController::class)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration — Signalements</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4">
            @include('admin.partials.nav')

            <div class="flex gap-3 text-sm mb-4">
                @foreach ($statuses as $value)
                    <a href="{{ route('admin.reports.index', ['status' => $value->value]) }}" class="{{ $status === $value ? 'font-bold underline' : '' }}">{{ $value->label() }}</a>
                @endforeach
            </div>

            <div class="space-y-3">
                @forelse ($reports as $report)
                    <div class="p-4 bg-white shadow rounded text-sm space-y-1">
                        <p>
                            <strong>{{ $report->reason->label() }}</strong> ·
                            @if ($link = $reportController::linkFor($report))
                                <a href="{{ $link }}" class="underline">{{ $reportController::describe($report) }}</a>
                            @else
                                <span class="text-gray-500">{{ $reportController::describe($report) }}</span>
                            @endif
                        </p>
                        @if ($report->details) <p class="italic">« {{ $report->details }} »</p> @endif
                        <p class="text-gray-500">Signalé par {{ $report->reporter?->name }} · {{ $report->created_at->diffForHumans() }}</p>

                        @if ($report->status === \App\Enums\ReportStatus::Pending)
                            <form method="POST" action="{{ route('admin.reports.handle', $report) }}" class="flex flex-wrap gap-2 pt-2">
                                @csrf
                                @foreach ($actions as $action => $label)
                                    <button name="action" value="{{ $action }}" class="px-2 py-1 border rounded {{ $action === 'remove' ? 'text-red-700' : '' }}"
                                        @if ($action === 'remove') onclick="return confirm('Supprimer le contenu (ou suspendre le compte) ?')" @endif>{{ $label }}</button>
                                @endforeach
                            </form>
                        @else
                            <p class="text-gray-500">{{ $report->status->label() }} par {{ $report->handler?->name }} · {{ $report->handled_at?->diffForHumans() }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600">Aucun signalement.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $reports->links() }}</div>
        </div>
    </div>
</x-app-layout>
