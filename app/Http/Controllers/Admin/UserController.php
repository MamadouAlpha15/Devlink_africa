<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Users: see, search, suspend, delete (cahier, section 21).
 */
class UserController extends Controller
{
    public const FILTERS = [
        'all' => 'Tous',
        'suspended' => 'Suspendus',
        'admins' => 'Administrateurs',
        'unverified' => 'Email non vérifié',
    ];

    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $filter = array_key_exists((string) $request->query('filter'), self::FILTERS) ? $request->query('filter') : 'all';

        $users = User::query()
            ->with('country')
            ->withCount(['projects', 'resources'])
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orWhere('username', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->when($filter === 'suspended', fn (Builder $query) => $query->whereNotNull('suspended_at'))
            ->when($filter === 'admins', fn (Builder $query) => $query->where('is_admin', true))
            ->when($filter === 'unverified', fn (Builder $query) => $query->whereNull('email_verified_at'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'q' => $q,
            'filter' => $filter,
            'filters' => self::FILTERS,
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanModerate($request->user(), $user);

        $user->forceFill(['suspended_at' => now()])->save();

        return back()->with('status', $user->name.' est suspendu : il ne peut plus se connecter et son profil est masqué.');
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null])->save();

        return back()->with('status', 'La suspension de '.$user->name.' est levée.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanModerate($request->user(), $user);

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Le compte de '.$name.' a été supprimé.');
    }

    /**
     * An admin cannot suspend or delete themselves or another admin (no lock-out of the platform).
     */
    private function ensureCanModerate(User $admin, User $user): void
    {
        abort_if($admin->is($user) || $user->is_admin, 403, 'Impossible de modérer un administrateur.');
    }
}
