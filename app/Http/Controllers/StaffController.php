<?php

namespace App\Http\Controllers;

use App\Helpers\Audit;
use App\Enums\Role;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $term = trim((string) $request->query('q', ''));

        $query = User::with('creator')->orderBy('name');

        match ($filter) {
            'active' => $query->where('is_active', true),
            'disabled' => $query->where('is_active', false),
            'former' => $query->onlyTrashed(),
            default => null,
        };

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return view('staff.index', [
            'users' => $query->paginate(25)->withQueryString(),
            'filter' => $filter,
            'counts' => [
                'all' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'disabled' => User::where('is_active', false)->count(),
                'former' => User::onlyTrashed()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('staff.create', ['roles' => Role::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
        ], [
            'email.unique' => 'That email is already in use, possibly by a former user. Check the Former tab and restore them instead.',
        ]);

        $user = new User(Arr::only($data, ['name', 'email', 'phone', 'job_title']));
        $user->password = $data['password'];
        $user->role = Role::from($data['role']);
        $user->email_verified_at = now();
        $user->created_by = $request->user()->id;
        $user->save();

        Audit::record('user.created', "Created {$user->name} ({$user->role->value})", $user);

        return redirect()->route('staff.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('staff.edit', [
            'user' => $user,
            'roles' => Role::cases(),
            'openBids' => Bid::open()->where('assigned_to', $user->id)->count(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(Role::class)],
        ], [
            'email.unique' => 'That email is already in use, possibly by a former user.',
        ]);

        $role = Role::from($data['role']);

        if ($role !== Role::Admin && $user->isLastActiveAdmin()) {
            return back()->withErrors(['role' => 'This is the last active admin. Make someone else an admin first.'])->withInput();
        }

        $user->fill(Arr::only($data, ['name', 'email', 'phone', 'job_title']));
        $user->role = $role;
        $changes = Audit::changes($user);
        $user->save();

        if ($changes) {
            Audit::record('user.updated', "Edited {$user->name} (".implode(', ', array_keys($changes)).')', $user, properties: ['changes' => $changes]);
        }

        return redirect()->route('staff.edit', $user)->with('status', 'User saved.');
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        if ($blocked = $this->blockedAction($request, $user, 'disable')) {
            return $blocked;
        }

        $user->is_active = false;
        $user->save();

        Audit::record('user.disabled', "Disabled {$user->name}", $user);

        return back()->with('status', "{$user->name} is disabled and can no longer sign in.");
    }

    public function enable(User $user): RedirectResponse
    {
        $user->is_active = true;
        $user->save();

        Audit::record('user.enabled', "Enabled {$user->name}", $user);

        return back()->with('status', "{$user->name} is enabled.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($blocked = $this->blockedAction($request, $user, 'delete')) {
            return $blocked;
        }

        $user->delete();

        Audit::record('user.removed', "Removed {$user->name}", $user);

        return redirect()->route('staff.index')->with('status', "{$user->name} was removed. Restore them from the Former tab if needed.");
    }

    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        Audit::record('user.restored', "Restored {$user->name}", $user);

        return redirect()->route('staff.index')->with('status', "{$user->name} was restored.");
    }

    public function sendResetLink(User $user): RedirectResponse
    {
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'The reset email could not be sent. Check the mail settings.');
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('error', 'A reset link was sent recently. Wait a minute and try again.');
        }

        Audit::record('user.reset_link_sent', "Sent a password reset link to {$user->name}", $user);

        return back()->with('status', "A reset link was sent to {$user->email}.");
    }

    private function blockedAction(Request $request, User $user, string $verb): ?RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', "You cannot {$verb} your own account. Ask another admin.");
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('error', "This is the last active admin, so they cannot be {$verb}d.");
        }

        return null;
    }
}
