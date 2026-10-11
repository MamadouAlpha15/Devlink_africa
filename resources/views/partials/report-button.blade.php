{{--
    Bouton « Signaler » réutilisable (cahier, section 22).
    @include('partials.report-button', ['type' => 'developer' | 'resource' | 'project' | 'comment', 'model' => $model, 'authorId' => …])
--}}
@auth
    @if (auth()->id() !== $authorId)
        <div x-data="{ open: false }" class="inline-block">
            <button type="button" @click="open = ! open" class="text-sm text-gray-500 underline">Signaler</button>

            <form x-show="open" x-cloak method="POST" action="{{ route('reports.store', [$type, $model->getKey()]) }}"
                  class="mt-2 p-3 bg-white border rounded shadow space-y-2 text-sm">
                @csrf
                <select name="reason" required class="block w-full border-gray-300 rounded text-sm">
                    <option value="">— Motif —</option>
                    @foreach (\App\Enums\ReportReason::cases() as $reason)
                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                    @endforeach
                </select>
                <textarea name="details" rows="2" maxlength="1000" class="block w-full border-gray-300 rounded text-sm" placeholder="Précisions (facultatif)"></textarea>
                <x-danger-button>Envoyer le signalement</x-danger-button>
            </form>
        </div>
    @endif
@endauth
