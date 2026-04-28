<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditHttpRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('activitylog.enabled', true) || ! config('activitylog.log_http_requests', true)) {
            return;
        }

        // تسجيل الدخول الناجح يُسجَّل يدويًا في LoginController لتجنب التكرار.
        if ($request->is('login') && $request->isMethod('POST')) {
            return;
        }

        if (! Auth::check()) {
            return;
        }

        try {
            activity()
                ->useLog('http')
                ->causedBy(Auth::user())
                ->withProperties([
                    'method' => $request->method(),
                    'full_url' => $request->fullUrl(),
                    'path' => '/'.$request->path(),
                    'route' => $request->route()?->getName(),
                    'route_parameters' => $this->serializeRouteParameters($request->route()?->parameters() ?? []),
                    'query' => $request->query(),
                    'payload' => $this->sanitizePayload($request),
                    'status' => $response->getStatusCode(),
                    'ip' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 400, ''),
                ])
                ->event($request->method())
                ->log($this->buildDescription($request));
        } catch (\Throwable) {
            //
        }
    }

    private function buildDescription(Request $request): string
    {
        $routeName = $request->route()?->getName();
        $target = $routeName ?: '/'.$request->path();

        return sprintf('%s %s', $request->method(), $target);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function serializeRouteParameters(array $parameters): array
    {
        $out = [];
        foreach ($parameters as $key => $value) {
            if ($value instanceof Model) {
                $out[$key] = [
                    '_model' => class_basename($value),
                    'id' => $value->getKey(),
                    'label' => $value->getAttribute('name')
                        ?? $value->getAttribute('title')
                        ?? $value->getAttribute('email'),
                ];
            } elseif (is_object($value)) {
                $out[$key] = (string) $value;
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|array{_truncated: true, _preview: string}
     */
    private function sanitizePayload(Request $request): array
    {
        $strip = ['_token', 'password', 'password_confirmation', 'current_password', 'remember'];
        $data = $request->except($strip);
        foreach (array_keys($request->allFiles()) as $key) {
            unset($data[$key]);
        }

        $fileKeys = array_keys($request->allFiles());
        if ($fileKeys !== []) {
            $data['_upload_fields'] = $fileKeys;
        }

        return $this->truncatePayload($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|array{_truncated: true, _preview: string}
     */
    private function truncatePayload(array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['_note' => 'تعذّر ترميز المحتوى للسجل'];
        }

        $max = 8000;
        if (strlen($json) <= $max) {
            return $data;
        }

        return [
            '_truncated' => true,
            '_preview' => mb_substr($json, 0, $max).'…',
        ];
    }
}
