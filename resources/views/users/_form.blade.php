@php
    $selectedExtras = collect(old('extra_permissions', $user->extra_permissions ?? []))->filter()->map(fn ($s) => (string) $s)->all();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="user-name">الاسم</label>
        <input id="user-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" required
               value="{{ old('name', $user->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-email">البريد الإلكتروني (لتسجيل الدخول)</label>
        <input id="user-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" required autocomplete="username"
               value="{{ old('email', $user->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-password">كلمة المرور @if(isset($editing) && $editing)<span class="text-muted fw-normal">(اتركها فارغة إن لم تتغير)</span>@endif</label>
        <input id="user-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if(!isset($editing) || !$editing) required @endif autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-password-confirmation">تأكيد كلمة المرور</label>
        <input id="user-password-confirmation" type="password" name="password_confirmation" class="form-control" @if(!isset($editing) || !$editing) required @endif autocomplete="new-password">
    </div>
    <div class="col-md-12">
        <label class="form-label" for="user-role">الدور</label>
        <select id="user-role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach ($roles as $key => $label)
                <option value="{{ $key }}" @selected(old('role', $user->role) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">الدور يحدد مجموعة الصلاحيات الافتراضية. يمكنك إضافة صلاحيات فوق الدور من القائمة أدناه.</div>
    </div>
    <div class="col-12">
        <label class="form-label">صلاحيات إضافية (اختياري — تُضاف إلى صلاحيات الدور)</label>
        <div class="border rounded p-3 bg-body-secondary bg-opacity-25" style="max-height: 280px; overflow-y: auto;">
            <div class="row g-2">
                @foreach ($permissions as $perm)
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="extra_permissions[]" value="{{ $perm->slug }}" id="perm-{{ $perm->id }}"
                                   @checked(in_array($perm->slug, $selectedExtras, true))>
                            <label class="form-check-label small" for="perm-{{ $perm->id }}">
                                <span class="font-monospace text-body-secondary">{{ $perm->slug }}</span>
                                <br><span class="text-body">{{ $perm->label }}</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @error('extra_permissions')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('extra_permissions.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>
