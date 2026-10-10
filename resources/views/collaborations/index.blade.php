{{--
    "Mes collaborations" — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/projets.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes collaborations</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif

            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Invitations reçues ({{ $receivedInvitations->count() }})</h3>
                @forelse ($receivedInvitations as $request)
                    <div class="border-b pb-2">
                        <a href="{{ route('projects.show', $request->project) }}" class="underline">{{ $request->project->title }}</a>
                        <span class="text-sm text-gray-600">par {{ $request->project->owner->name }} @if ($request->projectRole) · poste « {{ $request->projectRole->title }} » @endif</span>
                        @if ($request->message) <p class="text-sm italic">« {{ $request->message }} »</p> @endif
                        <form method="POST" action="{{ route('collaborations.accept', $request) }}" class="inline">@csrf <button class="text-sm underline text-green-700">Accepter</button></form>
                        <form method="POST" action="{{ route('collaborations.reject', $request) }}" class="inline">@csrf <button class="text-sm underline text-red-600">Refuser</button></form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucune invitation.</p>
                @endforelse
            </div>

            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Demandes reçues sur mes projets ({{ $receivedApplications->count() }})</h3>
                @forelse ($receivedApplications as $request)
                    <div class="border-b pb-2">
                        <a href="{{ route('devs.show', $request->user) }}" class="underline">{{ $request->user->name }}</a>
                        <span class="text-sm text-gray-600">
                            veut rejoindre <a href="{{ route('projects.show', $request->project) }}" class="underline">{{ $request->project->title }}</a>
                            @if ($request->projectRole) comme « {{ $request->projectRole->title }} » @endif
                        </span>
                        @if ($request->message) <p class="text-sm italic">« {{ $request->message }} »</p> @endif
                        <form method="POST" action="{{ route('collaborations.accept', $request) }}" class="inline">@csrf <button class="text-sm underline text-green-700">Accepter</button></form>
                        <form method="POST" action="{{ route('collaborations.reject', $request) }}" class="inline">@csrf <button class="text-sm underline text-red-600">Refuser</button></form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucune demande en attente.</p>
                @endforelse
            </div>

            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Mes demandes envoyées ({{ $sentApplications->count() }})</h3>
                @forelse ($sentApplications as $request)
                    <div class="text-sm">
                        <a href="{{ route('projects.show', $request->project) }}" class="underline">{{ $request->project->title }}</a> — en attente
                        <form method="POST" action="{{ route('collaborations.destroy', $request) }}" class="inline">@csrf @method('DELETE') <button class="underline">Annuler</button></form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucune demande en attente.</p>
                @endforelse
            </div>

            <div class="p-6 bg-white shadow rounded space-y-2">
                <h3 class="font-semibold">Historique</h3>
                @forelse ($history as $request)
                    <p class="text-sm">
                        {{ $request->direction->label() }} · {{ $request->user->name }} → {{ $request->project->title }} :
                        <strong>{{ $request->status->label() }}</strong>
                        <span class="text-gray-500">{{ $request->responded_at?->diffForHumans() }}</span>
                    </p>
                @empty
                    <p class="text-sm text-gray-600">Rien pour l'instant.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
