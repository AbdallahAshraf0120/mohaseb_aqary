<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\SalesImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * نقل البيانات القديمة: رفع ملف Excel → معاينة كل صف وأخطاؤه → تأكيد الاستيراد.
 */
class ImportController extends Controller
{
    public function __construct(private readonly SalesImportService $service) {}

    public function index(Project $project): View
    {
        return view('import.index', [
            'title' => 'استيراد من Excel | Mohaseb Aqary',
            'pageTitle' => 'استيراد البيانات القديمة من Excel',
            'project' => $project,
            'result' => null,
            'token' => null,
        ]);
    }

    public function template(Project $project): StreamedResponse
    {
        $book = $this->service->template();

        return response()->streamDownload(function () use ($book): void {
            (new Xlsx($book))->save('php://output');
        }, 'mohaseb-import-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Project $project, Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyUnlessAdmin($request)) {
            return $deny;
        }
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls,csv,txt']]);
        $token = Str::uuid()->toString();
        $ext = strtolower($request->file('file')->getClientOriginalExtension() ?: 'xlsx');
        $path = $request->file('file')->storeAs('imports', $token.'.'.$ext, 'local');

        return $this->run($project, $request, Storage::disk('local')->path($path), $token, false);
    }

    public function commit(Project $project, Request $request): View|RedirectResponse
    {
        if ($deny = $this->denyUnlessAdmin($request)) {
            return $deny;
        }
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $files = glob(Storage::disk('local')->path('imports/'.$data['token'].'.*')) ?: [];
        if ($files === []) {
            return redirect()->route('import.index', $project)->with('error', 'الملف انتهى؛ ارفعه تاني.');
        }
        $view = $this->run($project, $request, $files[0], $data['token'], true);
        @unlink($files[0]);

        return $view;
    }

    private function run(Project $project, Request $request, string $file, string $token, bool $commit): View|RedirectResponse
    {
        try {
            $rows = $this->service->readRows($file);
        } catch (\Throwable $e) {
            return redirect()->route('import.index', $project)->with('error', 'تعذّر قراءة الملف: '.mb_substr($e->getMessage(), 0, 150));
        }
        if ($rows === []) {
            return redirect()->route('import.index', $project)->with('error', 'الملف فاضي أو أول صف مش فيه أسماء الأعمدة.');
        }
        if ($missing = $this->service->missingColumns($rows)) {
            return redirect()->route('import.index', $project)->with('error', 'أعمدة ناقصة في الملف: '.implode('، ', $missing).'. نزّل النموذج واستخدمه.');
        }
        if (count($rows) > 2000) {
            return redirect()->route('import.index', $project)->with('error', 'الملف فيه أكتر من 2000 صف؛ قسّمه على أكتر من ملف.');
        }
        @set_time_limit(300);
        /** @var User $user */
        $user = $request->user();
        $result = $this->service->process($rows, $user, $commit, $request->boolean('keep_cashbox', true));

        if ($commit) {
            activity()->causedBy($user)->withProperties(['imported' => $result['imported'], 'failed' => $result['failed']])->log('sales_import');
        }

        return view('import.index', [
            'title' => 'استيراد من Excel | Mohaseb Aqary',
            'pageTitle' => 'استيراد البيانات القديمة من Excel',
            'project' => $project,
            'result' => $result,
            'token' => $commit ? null : $token,
            'committed' => $commit,
        ]);
    }

    private function denyUnlessAdmin(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User && $user->isAdmin()) {
            return null;
        }

        return back()->with('error', 'الاستيراد للأدمن بس.');
    }
}
