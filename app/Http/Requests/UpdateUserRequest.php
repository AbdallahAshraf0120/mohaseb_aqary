<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? (int) $user->getKey() : 0;
        $validSlugs = Permission::query()->pluck('slug')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(array_keys(config('roles', [])))],
            'extra_permissions' => ['nullable', 'array'],
            'extra_permissions.*' => ['string', Rule::in($validSlugs)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'role.in' => 'الدور المختار غير صالح.',
        ];
    }

    /**
     * @return array{name: string, email: string, password: ?string, role: string, extra_permissions: list<string>}
     */
    public function validatedPayload(): array
    {
        $data = $this->validated();
        $extras = collect($data['extra_permissions'] ?? [])
            ->filter()
            ->map(static fn ($s) => (string) $s)
            ->unique()
            ->values()
            ->all();

        $password = $data['password'] ?? null;
        $password = is_string($password) && $password !== '' ? $password : null;

        return [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => $password,
            'role' => (string) $data['role'],
            'extra_permissions' => $extras,
        ];
    }
}
