{{--
    DevLink AI — version fonctionnelle minimale (backend).
    Le front-end la restylera (c'est la fonctionnalité « WOW ») : voir docs/front-end/devlink-ai.md.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">DevLink AI</h2>
        <p class="text-sm text-gray-600">Décrivez qui vous cherchez, l'IA trouve les meilleurs profils.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 space-y-6">
            <form method="POST" action="{{ route('ai.store') }}" x-data="{ query: @js(old('query', '')) }" class="p-6 bg-white shadow rounded space-y-3">
                @csrf
                <textarea name="query" x-model="query" rows="3" maxlength="500" required
                          class="block w-full border-gray-300 rounded"
                          placeholder="Je cherche un développeur Flutter en Guinée pour travailler sur une application de livraison."></textarea>
                <x-input-error :messages="$errors->get('query')" />

                <div class="flex flex-wrap gap-2">
                    @foreach ($examples as $example)
                        <button type="button" @click="query = @js($example)" class="text-xs px-2 py-1 bg-gray-100 rounded">{{ $example }}</button>
                    @endforeach
                </div>

                <x-primary-button>Trouver les profils</x-primary-button>
            </form>

            @if ($recentSearches->isNotEmpty())
                <div class="p-6 bg-white shadow rounded">
                    <h3 class="font-semibold mb-2">Mes dernières recherches</h3>
                    <ul class="space-y-1 text-sm">
                        @foreach ($recentSearches as $search)
                            <li>
                                <a href="{{ route('ai.show', $search) }}" class="underline">{{ Str::limit($search->query, 90) }}</a>
                                <span class="text-gray-500">· {{ count($search->results ?? []) }} profil(s) · {{ $search->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
