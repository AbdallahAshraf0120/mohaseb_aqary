@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">المستخدمون</h5>
            @can('users.manage')
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus ms-1"></i> مستخدم جديد</a>
            @endcan
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('users.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted mb-0">بحث</label>
                    <input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="اسم، بريد، دور…">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">تصفية</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الدور</th>
                        <th class="text-center">صلاحيات إضافية</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td class="fw-medium">{{ $u->name }}</td>
                            <td class="small font-monospace">{{ $u->email }}</td>
                            <td><span class="badge text-bg-secondary">{{ $roles[$u->role] ?? $u->role }}</span></td>
                            <td class="text-center">{{ count($u->extra_permissions ?? []) }}</td>
                            <td class="text-end text-nowrap">
                                @can('users.manage')
                                    <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                    @if ((int) auth()->id() !== (int) $u->id)
                                        <form action="{{ route('users.destroy', $u) }}" method="post" class="d-inline" data-swal-confirm="{{ e('حذف هذا المستخدم؟') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون مطابقون.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
