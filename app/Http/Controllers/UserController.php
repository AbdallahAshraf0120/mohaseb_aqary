<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $q = trim((string) $request->query('q', ''));
        $usersQuery = User::query()->orderBy('name');
        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $usersQuery->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('role', 'like', $like);
            });
        }

        $roles = config('roles', []);

        return view('users.index', [
            'title' => 'المستخدمون | Mohaseb Aqary',
            'pageTitle' => 'المستخدمون',
            'users' => $usersQuery->paginate(20)->withQueryString(),
            'roles' => $roles,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'title' => 'مستخدم جديد | Mohaseb Aqary',
            'pageTitle' => 'إضافة مستخدم',
            'user' => new User(['role' => 'viewer', 'extra_permissions' => []]),
            'roles' => config('roles', []),
            'permissions' => Permission::query()->orderBy('label')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validatedPayload();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'extra_permissions' => $data['extra_permissions'] === [] ? null : $data['extra_permissions'],
        ]);

        return redirect()->route('users.index')->with('success', 'تم إنشاء المستخدم. إن وُجدت صلاحيات مخصصة فهي الوحيدة المعتمدة؛ وإلا تُستخدم صلاحيات الدور.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'title' => 'تعديل مستخدم | Mohaseb Aqary',
            'pageTitle' => 'تعديل مستخدم',
            'user' => $user,
            'roles' => config('roles', []),
            'permissions' => Permission::query()->orderBy('label')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validatedPayload();

        if ($user->isAdmin() && $data['role'] !== 'admin') {
            $otherAdmins = User::query()
                ->where('role', 'admin')
                ->where('id', '!=', (int) $user->id)
                ->count();
            if ($otherAdmins < 1) {
                return redirect()
                    ->route('users.edit', $user)
                    ->withErrors(['role' => 'يجب أن يبقى مدير واحد على الأقل في النظام.'])
                    ->withInput();
            }
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'extra_permissions' => $data['extra_permissions'] === [] ? null : $data['extra_permissions'],
        ];
        if ($data['password'] !== null) {
            $payload['password'] = $data['password'];
        }
        $user->update($payload);

        return redirect()->route('users.index')->with('success', 'تم تحديث بيانات المستخدم.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->isAdmin()) {
            $admins = User::query()->where('role', 'admin')->count();
            if ($admins <= 1) {
                return redirect()->route('users.index')->with('error', 'لا يمكن حذف آخر مدير في النظام.');
            }
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم.');
    }
}
