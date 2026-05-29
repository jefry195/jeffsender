<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Tangkap error CSRF (419) dan redirect kembali dengan pesan agar token refresh otomatis
        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect()->back()->with('danger', __('Sesi Anda telah berakhir karena tidak ada aktivitas. Halaman telah disegarkan, silakan coba lagi.'));
        });
    }
}
