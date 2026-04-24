@extends('layouts.admin')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل مستخدم</h5>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form', ['editing' => true])
                <button type="submit" class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
