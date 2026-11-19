@extends('layouts.admin')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">العملاء المكررين ({{ $groups->count() }} مجموعة)</h5>
            <a href="{{ route('clients.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع للعملاء</a>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary">بنعتبر العميل مكرر لو ليه نفس الرقم القومي، أو نفس الموبايل ونفس الاسم. الدمج بينقل كل البيعات والعقود والتحصيلات للعميل اللي تختاره ويمسح الباقي.</p>
            @forelse ($groups as $group)
                <form method="post" action="{{ route('clients.merge', $project) }}" class="border rounded-3 p-3 mb-3"
                      onsubmit="return confirm('تأكيد دمج العملاء دول في عميل واحد؟')">
                    @csrf
                    <table class="table table-sm align-middle mb-2">
                        <thead><tr><th style="width:5rem">يفضل</th><th>الاسم</th><th>الموبايل</th><th>الرقم القومي</th><th>البيعات</th><th>اتسجل</th></tr></thead>
                        <tbody>
                        @foreach ($group as $c)
                            <tr>
                                <td><input type="radio" class="form-check-input" name="keep_id" value="{{ $c->id }}" @checked($loop->first)>
                                    <input type="hidden" name="ids[]" value="{{ $c->id }}"></td>
                                <td><a href="{{ route('clients.show', [$project, $c]) }}">{{ $c->name }}</a></td>
                                <td class="font-monospace">{{ $c->phone }}</td>
                                <td class="font-monospace">{{ $c->national_id ?: '—' }}</td>
                                <td>{{ $c->sales_count }}</td>
                                <td class="small">{{ optional($c->created_at)->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if (auth()->user()?->isAdmin())
                        <button class="btn btn-primary btn-sm">دمج في العميل المختار</button>
                    @else
                        <span class="small text-body-secondary">الدمج للأدمن بس.</span>
                    @endif
                </form>
            @empty
                <div class="text-center text-muted py-4">مفيش عملاء مكررين.</div>
            @endforelse
        </div>
    </div>
@endsection
