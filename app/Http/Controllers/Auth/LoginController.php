<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'تسجيل الدخول | Mohaseb Aqary',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // حماية من تخمين كلمة السر: 5 محاولات غلط لنفس الإيميل من نفس الجهاز، وبعدها انتظار.
        $throttleKey = Str::transliterate(Str::lower((string) $credentials['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => 'محاولات دخول كتير. حاول تاني بعد '.max(1, (int) ceil($seconds / 60)).' دقيقة.',
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 15 * 60);

            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();
        \activity()
            ->useLog('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ])
            ->log('تسجيل دخول');

        return redirect()->intended($this->landingUrlFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            \activity()
                ->useLog('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'ip' => $request->ip(),
                ])
                ->log('تسجيل خروج');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * أول صفحة مسموح للمستخدم يفتحها بعد الدخول (بدل ما يقابل «ليس لديك صلاحية» لو مالوش صلاحية المشاريع).
     */
    private function landingUrlFor(?\App\Models\User $user): string
    {
        $map = config('route-permissions', []);
        foreach (['projects.index', 'crm-leads.index', 'tasks.mine', 'tasks.index', 'land-trading.index', 'global-cashbox.index', 'shareholders.index'] as $route) {
            $slug = $map[$route] ?? $route;
            if ($user && \Illuminate\Support\Facades\Route::has($route) && $user->can($slug)) {
                return route($route);
            }
        }

        return route('projects.index');
    }
}
