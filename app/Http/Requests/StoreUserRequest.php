<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        $validSlugs = Permission::query()->pluck('slug')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
     * @return array{name: string, email: string, password: string, role: string, extra_permissions: list<string>}
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

        return [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => (string) $data['password'],
            'role' => (string) $data['role'],
            'extra_permissions' => $extras,
        ];
    }
}
