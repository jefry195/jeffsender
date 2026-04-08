<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// todo: remove all of these before release
Route::get('auth/{id}', function ($id = false) {
    if (! $id) {
        return redirect('/user/dashboard');
    }

    Auth::loginUsingId($id, true);
    request()->session()->regenerate();

    return inertia_location('/user/dashboard');
})->where('id', '[0-9]+');

Route::match(['get', 'post'], '/system/cli', function (Request $request) {
    $output = '';
    $command = $request->input('command', '');

    if ($request->isMethod('post')) {
        if ($command) {
            try {
                Artisan::call($command);
                $output = Artisan::output();
            } catch (\Exception $e) {
                $output = $e->getMessage();
            }
        } else {
            $output = 'No command entered.';
        }
    }

    $csrf_field = csrf_field();
    $form_html = <<<HTML
    <h1>Artisan CLI Runner</h1>
    <form method="POST" action="/system/cli">
        {$csrf_field}
        <label for="command">php artisan</label>
        <input type="text" name="command" id="command" value="{$command}" size="100" autofocus>
        <button type="submit">Run</button>
    </form>
HTML;

    if ($request->isMethod('post')) {
        $form_html .= '<h2>Output:</h2><pre>'.e($output).'</pre>';
    }

    return response($form_html);
});
