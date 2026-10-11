{{--
    Détail d'un projet — version fonctionnelle minimale (backend).
    Le front-end la restylera : voir docs/front-end/projets.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->title }}</h2>
        <p class="text-gray-600">
            {{ $project->category->label() }} · {{ $project->status->label() }}
            @if ($project->country) · {{ $project->country->flag }} {{ $project->country->name }} @endif
            · par <a href="{{ route('devs.show', $project->owner) }}" class="underline">{{ $project->owner->name }}</a>
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 bg-red-50 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif

            @if ($project->coverUrl())
                <img src="{{ $project->coverUrl() }}" alt="" class="w-full max-h-72 object-cover rounded">
            @endif

            {{-- Actions du visiteur --}}
            <div class="flex flex-wrap gap-3 items-center">
                @if ($canUpdate)
                    <a href="{{ route('projects.edit', $project) }}" class="underline">Modifier le projet</a>
                @endif
                @include('partials.favorite-button', ['type' => 'project', 'model' => $project])

                @if ($viewerMembership && $viewerMembership->role !== \App\Enums\ProjectMemberRole::Owner)
                    <form method="POST" action="{{ route('projects.leave', $project) }}" onsubmit="return confirm('Quitter ce projet ?')">
                        @csrf
                        <x-secondary-button type="submit">Quitter le projet</x-secondary-button>
                    </form>
                @endif

                @if ($viewerPendingRequest)
                    <p class="text-sm">
                        @if ($viewerPendingRequest->direction === \App\Enums\RequestDirection::Invitation)
                            Vous êtes invité à rejoindre ce projet.
                            <form method="POST" action="{{ route('collaborations.accept', $viewerPendingRequest) }}" class="inline">@csrf <button class="underline">Accepter</button></form>
                            <form method="POST" action="{{ route('collaborations.reject', $viewerPendingRequest) }}" class="inline">@csrf <button class="underline">Refuser</button></form>
                        @else
                            Votre demande est en attente.
                            <form method="POST" action="{{ route('collaborations.destroy', $viewerPendingRequest) }}" class="inline">@csrf @method('DELETE') <button class="underline">Annuler</button></form>
                        @endif
                    </p>
                @elseif ($canApply)
                    <form method="POST" action="{{ route('projects.requests.store', $project) }}" class="w-full p-4 bg-white shadow rounded space-y-2">
                        @csrf
                        <h3 class="font-semibold">Rejoindre ce projet</h3>
                        <select name="project_role_id" class="border-gray-300 rounded">
                            <option value="">Poste : peu importe</option>
                            @foreach ($project->roles->reject->isFull() as $role)
                                <option value="{{ $role->id }}">{{ $role->title }}</option>
                            @endforeach
                        </select>
                        <textarea name="message" rows="2" maxlength="1000" class="block w-full border-gray-300 rounded" placeholder="Bonjour, j'aimerais rejoindre ton projet en tant que…"></textarea>
                        <x-primary-button>Envoyer ma demande</x-primary-button>
                    </form>
                @elseif (! auth()->check() && $project->acceptsCollaborators())
                    <a href="{{ route('login') }}" class="underline">Connectez-vous pour rejoindre ce projet</a>
                @endif
            </div>

            <div class="p-6 bg-white shadow rounded">
                <p class="whitespace-pre-line">{{ $project->description }}</p>
                <p class="mt-4 text-sm">Technologies : {{ $project->skills->pluck('name')->join(', ') ?: '—' }}</p>
                <p class="text-sm">Membres recherchés : {{ $project->members_wanted }}</p>
            </div>

            {{-- Recruter une équipe --}}
            <div class="p-6 bg-white shadow rounded space-y-3">
                <h3 class="font-semibold">Profils recherchés</h3>
                @forelse ($project->roles as $role)
                    <div class="flex flex-wrap items-center gap-2">
                        <span>{{ $role->title }} ×{{ $role->slots }}</span>
                        <span class="text-sm {{ $role->isFull() ? 'text-gray-500' : 'text-green-700' }}">
                            {{ $role->isFull() ? 'Pourvu' : $role->openSlots().' place(s) libre(s)' }}
                        </span>
                        @if ($canManage)
                            <form method="POST" action="{{ route('projects.roles.destroy', [$project, $role]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-600">Supprimer</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Aucun poste défini.</p>
                @endforelse

                @if ($canManage)
                    <form method="POST" action="{{ route('projects.roles.store', $project) }}" class="flex flex-wrap gap-2 pt-2 border-t">
                        @csrf
                        <x-text-input name="title" placeholder="ex. UI/UX Designer" required />
                        <select name="skill_id" class="border-gray-300 rounded">
                            <option value="">Compétence</option>
                            @foreach ($skills as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="slots" type="number" min="1" max="10" value="1" class="w-20" />
                        <x-primary-button>Ajouter un poste</x-primary-button>
                    </form>
                @endif
            </div>

            {{-- Équipe --}}
            <div class="p-6 bg-white shadow rounded space-y-2">
                <h3 class="font-semibold">Équipe ({{ $project->memberships->count() }})</h3>
                @foreach ($project->memberships as $membership)
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('devs.show', $membership->user) }}" class="underline">{{ $membership->user->name }}</a>
                        <span class="text-sm text-gray-600">
                            {{ $membership->role->label() }}
                            @if ($membership->projectRole) · {{ $membership->projectRole->title }} @endif
                            @if ($membership->user->country) · {{ $membership->user->country->flag }} @endif
                        </span>

                        @if ($canManage && $membership->role !== \App\Enums\ProjectMemberRole::Owner)
                            @if ($canUpdate)
                                <form method="POST" action="{{ route('projects.members.update', [$project, $membership->user]) }}" class="inline">
                                    @csrf @method('PATCH')
                                    @if ($membership->role === \App\Enums\ProjectMemberRole::CoOwner)
                                        <input type="hidden" name="role" value="member">
                                        <button class="text-sm underline">Rétrograder</button>
                                    @else
                                        <input type="hidden" name="role" value="co_owner">
                                        <button class="text-sm underline">Promouvoir co-responsable</button>
                                    @endif
                                </form>
                            @endif
                            <form method="POST" action="{{ route('projects.members.destroy', [$project, $membership->user]) }}" class="inline" onsubmit="return confirm('Retirer ce membre ?')">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-600">Retirer</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($canManage)
                {{-- Demandes reçues --}}
                <div class="p-6 bg-white shadow rounded space-y-3">
                    <h3 class="font-semibold">Demandes pour rejoindre ({{ $pendingApplications->count() }})</h3>
                    @forelse ($pendingApplications as $request)
                        <div class="border-b pb-2">
                            <a href="{{ route('devs.show', $request->user) }}" class="underline">{{ $request->user->name }}</a>
                            <span class="text-sm text-gray-600">
                                {{ $request->user->country?->flag }} · {{ $request->user->skills->take(3)->pluck('name')->join(', ') }}
                                @if ($request->projectRole) · pour « {{ $request->projectRole->title }} » @endif
                            </span>
                            @if ($request->message) <p class="text-sm italic">« {{ $request->message }} »</p> @endif
                            <form method="POST" action="{{ route('collaborations.accept', $request) }}" class="inline">@csrf <button class="text-sm underline text-green-700">Accepter</button></form>
                            <form method="POST" action="{{ route('collaborations.reject', $request) }}" class="inline">@csrf <button class="text-sm underline text-red-600">Refuser</button></form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">Aucune demande en attente.</p>
                    @endforelse

                    @if ($pendingInvitations->isNotEmpty())
                        <h4 class="font-semibold pt-2">Invitations envoyées</h4>
                        @foreach ($pendingInvitations as $invitation)
                            <div class="text-sm">
                                {{ $invitation->user->name }} @if ($invitation->projectRole) ({{ $invitation->projectRole->title }}) @endif
                                <form method="POST" action="{{ route('collaborations.destroy', $invitation) }}" class="inline">@csrf @method('DELETE') <button class="underline">Annuler</button></form>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Chercher des collaborateurs --}}
                <div class="p-6 bg-white shadow rounded space-y-3">
                    <h3 class="font-semibold">Développeurs suggérés pour vos postes</h3>
                    @forelse ($suggestions as $suggestion)
                        <div>
                            <p class="font-medium">{{ $suggestion['role']->title }}</p>
                            @forelse ($suggestion['developers'] as $developer)
                                <form method="POST" action="{{ route('projects.invitations.store', $project) }}" class="text-sm flex gap-2 items-center">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $developer->id }}">
                                    <input type="hidden" name="project_role_id" value="{{ $suggestion['role']->id }}">
                                    <a href="{{ route('devs.show', $developer) }}" class="underline">{{ $developer->name }}</a>
                                    <span class="text-gray-600">{{ $developer->country?->flag }} {{ $developer->city }} · {{ $developer->devscore }} pts</span>
                                    <button class="underline">Inviter</button>
                                </form>
                            @empty
                                <p class="text-sm text-gray-600">Aucun développeur disponible pour le moment.</p>
                            @endforelse
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">Ajoutez des postes avec une compétence pour recevoir des suggestions.</p>
                    @endforelse
                    <a href="{{ route('devs.index') }}" class="text-sm underline">Rechercher d'autres développeurs</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
