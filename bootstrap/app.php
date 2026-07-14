<?php

use App\Mail\ErrorMail;
use App\Services\EmailService;
use App\Http\Middleware\Office\CheckRoutePermission;
use App\Http\Middleware\Secrets\EnsureSecretsAdmin;
use App\Http\Middleware\Secrets\NoStoreCache;
use App\Http\Middleware\TouchAdminActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.permission' => CheckRoutePermission::class,
            'secrets.admin' => EnsureSecretsAdmin::class,
            'secrets.nostore' => NoStoreCache::class,
            'touch.activity' => TouchAdminActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->respond(function (Response $response, Throwable $exception) {
            // try-catch以外で500エラーが発生した場合メール通知する。
            if ($response->getStatusCode() === 500) {
                $subject = config('app.env').' 500エラー発生 | '.config('app.name');
                $to = config('app.error_notice_address');
                if ($to) {
                    $data = ['assign' => ['exception' => $exception]];
                    Mail::to($to)->queue(new ErrorMail($subject, $data));
                }
            }

            if (view()->exists("office/errors/{$response->getStatusCode()}")) {
                return response()->view("office/errors/{$response->getStatusCode()}", status: $response->getStatusCode());
            }

            return $response;
        });
    })->create();
