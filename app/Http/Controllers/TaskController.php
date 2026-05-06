<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $status = trim((string) $request->query('status', ''));

        $query = Task::query()->with(['creator:id,name', 'assignee:id,name']);

        if (! $request->user()?->can('tasks.manage')) {
            $query->where('assigned_to', $request->user()?->id);
        }

        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($q) use ($like): void {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $filters->applyWhereDate($query, 'created_at');

        $tasks = $query
            ->orderByRaw("case status when 'open' then 0 when 'in_progress' then 1 when 'done' then 2 else 3 end")
            ->orderByRaw('due_at is null, due_at asc')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('tasks.index', [
            'title' => 'المهام | Mohaseb Aqary',
            'pageTitle' => 'المهام',
            'tasks' => $tasks,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('tasks.manage'), 403);

        $brokers = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('tasks.create', [
            'title' => 'إضافة مهمة | Mohaseb Aqary',
            'pageTitle' => 'إضافة مهمة',
            'brokers' => $brokers,
            'task' => new Task(['status' => 'open', 'priority' => 'normal']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('tasks.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', Rule::in(['open', 'in_progress', 'done', 'cancelled'])],
            'priority' => ['required', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
        ]);

        $data['created_by'] = $request->user()?->id;

        $task = Task::query()->create($data);

        return redirect()->route('tasks.show', $task)->with('success', 'تم إنشاء المهمة.');
    }

    public function show(Task $task, Request $request): View
    {
        $this->ensureTaskAccessible($task, $request);

        $task->load([
            'creator:id,name',
            'assignee:id,name',
            'updates' => fn ($q) => $q->with(['user:id,name'])->latest('happened_at')->latest('id'),
        ]);

        return view('tasks.show', [
            'title' => 'تفاصيل المهمة | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل المهمة',
            'task' => $task,
        ]);
    }

    public function update(Task $task, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('tasks.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'string', Rule::in(['open', 'in_progress', 'done', 'cancelled'])],
            'priority' => ['required', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
        ]);

        $task->update($data);

        return redirect()->route('tasks.show', $task)->with('success', 'تم تحديث المهمة.');
    }

    public function destroy(Task $task, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('tasks.manage'), 403);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'تم حذف المهمة.');
    }

    public function storeUpdate(Task $task, Request $request): RedirectResponse
    {
        $this->ensureTaskAccessible($task, $request);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
            'happened_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['open', 'in_progress', 'done'])],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $path = null;
        $originalName = null;
        $mime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('task-attachments', 'public');
            $originalName = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
        }

        TaskUpdate::query()->create([
            'task_id' => $task->id,
            'user_id' => $request->user()?->id,
            'note' => $data['note'] ?? null,
            'happened_at' => $data['happened_at'] ?? now(),
            'attachment_path' => $path,
            'attachment_name' => $originalName,
            'attachment_mime' => $mime,
        ]);

        if (! empty($data['status'])) {
            if ($request->user()?->can('tasks.manage') || (int) $task->assigned_to === (int) ($request->user()?->id ?? 0)) {
                $task->update(['status' => $data['status']]);
            }
        } elseif ($task->status === 'open') {
            $task->update(['status' => 'in_progress']);
        }

        return redirect()->route('tasks.show', $task)->with('success', 'تم إضافة تحديث على المهمة.');
    }

    private function ensureTaskAccessible(Task $task, Request $request): void
    {
        if ($request->user()?->can('tasks.manage')) {
            return;
        }

        if ((int) $task->assigned_to !== (int) ($request->user()?->id ?? 0)) {
            abort(403, 'ليس لديك صلاحية للوصول لهذه المهمة.');
        }
    }
}

