<?php

use App\Helpers\PlanPerks;
use App\Models\Option;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Nwidart\Modules\Facades\Module;

if (! function_exists('amount_format')) {
    /**
     *  format amount
     *
     * @param  string  $amount
     * @param  string  $icon_type
     * @return string
     */
    function amount_format($amount = 0, $icon_type = 'name')
    {
        $currency = (object) get_option('base_currency');
        if ($icon_type == 'name') {
            $currency = $currency->position == 'right' ? $currency->name.' '.number_format($amount, 2) : number_format($amount, 2).' '.$currency->name;
        } elseif ($icon_type == 'both') {
            $currency = $currency->icon.number_format($amount, 2).' '.$currency->name;
        } else {
            $currency = $currency->position == 'right' ? number_format($amount, 2).$currency->icon : $currency->icon.number_format($amount, 2);
        }

        return $currency;
    }
}

if (! function_exists('adjust_option_urls')) {
    function adjust_option_urls($value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = adjust_option_urls($v);
            }
        } elseif (is_string($value)) {
            $base = request() ? request()->root() : config('app.url');
            
            if (Str::contains($value, 'https://wa.doorenzcreative.com')) {
                $value = str_replace('https://wa.doorenzcreative.com', $base, $value);
            }
            
            if (Str::startsWith($value, '/uploads/') || Str::startsWith($value, '/assets/')) {
                $value = rtrim($base, '/') . '/' . ltrim($value, '/');
            }
        }
        return $value;
    }
}

if (! function_exists('get_option')) {
    /**
     * Get Settings From Database
     *
     * @param  $locale
     */
    function get_option($key, bool $withLocale = false): mixed
    {
        if (!file_exists(base_path('public/uploads/installed'))) {
            return null;
        }
        $restKeys = null;
        if (Str::contains($key, '.')) {
            $restKeys = Str::after($key, '.');
            $key = Str::before($key, '.');
        }

        $value = Cache::remember(
            $withLocale ? $key.'_'.current_locale() : $key,
            env('CACHE_LIFETIME', 1800),
            function () use ($key, $withLocale) {
                $query = Option::query()->where('key', $key);
                if ($withLocale) {
                    $val = (clone $query)->where('lang', current_locale())->value('value');
                    if ($val !== null) {
                        return $val;
                    }
                    $val = (clone $query)->where('lang', 'en')->value('value');
                    if ($val !== null) {
                        return $val;
                    }
                }
                return $query->value('value');
            }
        );

        $result = data_get($value, $restKeys, '');
        return adjust_option_urls($result);
    }
}

if (! function_exists('get_option_with_locale')) {
    function get_option_with_locale(string $key, ?string $language = null, bool $includeDefault = false): mixed
    {
        $language ??= current_locale();
        $cacheKey = "{$key}_{$language}";

        $value = cache_remember($cacheKey, function () use ($key, $language, $includeDefault) {
            $option = Option::query()
                ->where('key', $key)
                ->where('lang', $language)
                ->first();

            // Fallback: if no record found for current locale, fetch any matching record
            if (! $option) {
                $option = Option::query()->where('key', $key)->first();
                if ($option && $includeDefault) {
                    $optionClone = $option->replicate();
                    $optionClone->lang = $language;
                    $optionClone->save();
                }
            }

            return $option?->value ?? [];
        });

        return adjust_option_urls($value);
    }
}

if (! function_exists('cache_remember')) {
    /**
     * This function will remember the cache
     */
    function cache_remember(string $key, callable $callback, int $ttl = 1800): mixed
    {
        return cache()->remember($key, env('CACHE_LIFETIME', $ttl), $callback);
    }
}

if (! function_exists('current_locale')) {
    /**
     * Get Current Locale
     * Return current locale|lang
     *
     * @return string|null
     */
    function current_locale()
    {
        return session('locale', app()->getLocale());
    }
}

if (! function_exists('getTranslationFile')) {
    function getTranslationFile()
    {
        $file = base_path('/lang/'.session('locale', 'en').'.json');
        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return [];
    }
}

if (! function_exists('getCookieConsent')) {
    function getCookieConsent()
    {
        if (! request()->isSecure()) {
            return true;
        }
        $cookieKey = Str::slug(env('APP_NAME', 'laravel'), '_').'_cookie_consent';
        $cookieHeader = request()->header('Cookie');
        parse_str(str_replace('; ', '&', $cookieHeader), $cookies);
        $cookieValue = isset($cookies[$cookieKey]) ? json_decode($cookies[$cookieKey]) : false;

        return $cookieValue->$cookieKey ?? false;
    }
}

if (! function_exists('activeWorkspaceOwnerId')) {
    /**
     * Get the owner id of the currently active workspace.
     *
     * @return int|null
     */
    function activeWorkspaceOwnerId()
    {
        /**
         * @var User
         */
        $authUser = auth()->user();

        if (! $authUser) {
            return null;
        }

        return $authUser?->getActiveWorkspaceOwnerId() ?? $authUser->id;
    }
}

if (! function_exists('activeWorkspaceOwner')) {
    function activeWorkspaceOwner()
    {
        return User::find(activeWorkspaceOwnerId());
    }
}

function getRequestModuleName(): Stringable
{
    $activeModule = null;
    $secondRouteSegment = request()->segment(2) ?? false;
    $module = Module::find(
        str($secondRouteSegment)
            ->remove('-')
            ->toString()
    );

    if ($module) {
        $activeModule = str($module->getName())->studly()->toString();
    }

    return str($activeModule);
}

function getPlatformModules()
{
    return collect(Module::allEnabled())
        ->filter(fn ($module) => $module->get('accessible', false))
        ->filter(fn ($module) => $module->get('module_type') === 'platform')
        ->map(fn ($module) => str($module->getName())->kebab()->toString())
        ->values()
        ->all();
}

function getActiveModules()
{
    return collect(Module::allEnabled())
        ->filter(fn ($module) => $module->get('accessible', false))
        ->map(fn ($module) => str($module->getName())->kebab()->toString())
        ->values()
        ->all();
}

function validateUserPlan(string $planKey, bool $toArray = false, ?int $userId = null): array|bool
{
    return PlanPerks::checkPlan($planKey, $toArray, $userId);
}

function validateWorkspacePlan(string $planKey, bool $toArray = false): array|bool
{
    return PlanPerks::checkPlan($planKey, $toArray, activeWorkspaceOwnerId());
}

function logOnDebug(string|Stringable $message, array $context = []): void
{
    if (app()->hasDebugModeEnabled()) {
        Log::debug($message, $context);
    }
}

function executeSilentCommand(string $command): ?string
{
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open($command, $descriptorspec, $pipes, null, null, [
        'create_no_window' => true
    ]);

    if (is_resource($process)) {
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return $output;
    }

    return null;
}

