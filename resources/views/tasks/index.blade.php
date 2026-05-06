@extends('layouts.admin')

@section('content')
    <x-listing.filters
        :placeholder="'عنوان المهمة أو تفاصيل…'"
        :help="'تصفية حسب تاريخ الإنشاء.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">المهام</h5>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('status'))
                    <span class="badge text-bg-primary">فلاتر نشطة</span>
                @endif
            </div>
            @can('tasks.manage')
                <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-circle-plus ms-1"></i> إضافة مهمة
                </a>
            @endcan
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-3">
                @php
                    $statuses = [
                        '' => 'الكل',
                        'open' => 'مفتوحة',
                        'in_progress' => 'قيد التنفيذ',
                        'done' => 'تمت',
                        'cancelled' => 'ملغاة',
                    ];
                @endphp
                @foreach ($statuses as $key => $label)
                    @php
                        $active = (string) $status === (string) $key;
                        $qs = request()->query();
                        if ($key === '') {
                            unset($qs['status']);
                        } else {
                            $qs['status'] = $key;
                        }
                    @endphp
                    <a href="{{ route('tasks.index', $qs) }}"
                       class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العنوان</th>
                        <th>المسندة إلى</th>
                        <th>الحالة</th>
                        <th>الأولوية</th>
                        <th>الاستحقاق</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($tasks as $task)
                        @php
                            $statusBadge = match ($task->status) {
                                'open' => 'text-bg-info',
                                'in_progress' => 'text-bg-primary',
                                'done' => 'text-bg-success',
                                'cancelled' => 'text-bg-secondary',
                                default => 'text-bg-light',
                            };
                            $priorityBadge = match ($task->priority) {
                                'urgent' => 'text-bg-danger',
                                'high' => 'text-bg-warning',
                                'normal' => 'text-bg-light border',
                                'low' => 'text-bg-secondary',
                                default => 'text-bg-light border',
                            };
                            $isOverdue = $task->due_at && $task->status !== 'done' && $task->due_at->lt(now());
                        @endphp
                        <tr @if($isOverdue) class="table-warning" @endif>
                            <td>{{ $tasks->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $task->title }}</td>
                            <td>{{ $task->assignee?->name ?? '—' }}</td>
                            <td><span class="badge {{ $statusBadge }}">{{ $statuses[$task->status] ?? $task->status }}</span></td>
                            <td><span class="badge {{ $priorityBadge }}">{{ $task->priority }}</span></td>
                            <td class="small font-monospace">
                                {{ $task->due_at?->format('Y-m-d H:i') ?? '—' }}
                                @if ($isOverdue)
                                    <span class="badge text-bg-danger ms-1">متأخرة</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                @can('tasks.manage')
                                    <form action="{{ route('tasks.destroy', $task) }}"
                                          method="post"
                                          class="d-inline"
                                          data-swal-confirm="{{ e('هل تريد حذف هذه المهمة؟') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد مهام.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $tasks->links() }}</div>
        </div>
    </div>
@endsection

