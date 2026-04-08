<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Option;
use App\Helpers\PageHeader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Prism;
use App\Exceptions\SessionException;

class LanguageController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:language');
    }

    public function index()
    {
        PageHeader::set(__('Languages'))->addModal(__('Add New'), 'createModal', 'bx:plus');
        $languages = get_option('languages');
        $countries = json_decode(file_get_contents(base_path('lang/langlist.json')), true);

        return Inertia::render('Admin/Language/Index', [
            'languages' => $languages,
            'countries' => $countries
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'language_code' => [
                'required',
                'string',
                Rule::unique('options', 'value->language_code')
            ],
            'name' => 'required|string|max:100',
        ]);

        $targetFile = base_path("lang/{$validated['language_code']}.json");

        if (file_exists($targetFile)) {
            return back()->withErrors(['language_code' => 'This language already exists.']);
        }

        File::copy(
            base_path('lang/default.json'),
            $targetFile
        );

        $languages = get_option('languages') ?? [];
        $languages[$validated['language_code']] = $validated['name'];

        Option::updateOrCreate(
            ['key' => 'languages'],
            ['value' => $languages]
        );

        Cache::forget('languages');

        return back()->with('success', 'Language created successfully.');
    }

    public function show(Request $request, $id)
    {
        $pageHeader = PageHeader::set(__('Language :id', ['id' => $id]));
        if (file_exists(base_path('lang/backups/' . $id . '_backup.json'))) {
            $pageHeader->addModal(__('Revert Last Backup'), 'revertModal', 'mdi:backup-restore');
        }
        $pageHeader
            ->addModal(__('Translate With AI'), 'aiConfirmModal', 'material-symbols:translate')
            ->addModal(__('Add New'), 'createModal', 'bx:plus')
            ->addBackLink(route('admin.language.index'));

        $file = base_path('lang/' . $id . '.json');
        abort_if(!file_exists($file), 404);

        $allLanguages = json_decode(file_get_contents($file), true);
        $languageCollection = collect($allLanguages);

        $searchQuery = $request->input('search');
        $perPage = (int) $request->input('perPage', 15);
        $currentPage = (int) $request->input('page', 1);

        $filteredCollection = $languageCollection;
        if ($searchQuery) {
            $filteredCollection = $languageCollection->filter(function ($value, $key) use ($searchQuery) {
                return str_contains(strtolower($key), strtolower($searchQuery)) ||
                    str_contains(strtolower($value), strtolower($searchQuery));
            });
        }

        $total = $filteredCollection->count();
        $paginatedItems = $filteredCollection->forPage($currentPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $fileModifiedAt = filemtime($file);
        $hasBackup = file_exists(base_path('lang/backups/' . $id . '_backup.json'));

        return Inertia::render('Admin/Language/Show', [
            'posts' => $paginator,
            'id' => $id,
            'filters' => $request->only(['search', 'perPage']),
            'fileModifiedAt' => $fileModifiedAt,
            'hasBackup' => $hasBackup
        ]);
    }


    public function update(Request $request, $id)
    {
        $file = base_path('lang/' . $id . '.json');

        if (!file_exists($file)) {
            abort(404, 'Language file not found');
        }

        $validated = $request->validate([
            'values' => 'required|array',
            'values.*' => 'string|max:10000',
        ]);

        $existingData = json_decode(File::get($file), true);

        if (!is_array($existingData)) {
            return back()->withErrors(['file' => 'Invalid language file format.']);
        }

        foreach ($validated['values'] as $key => $value) {

            if (array_key_exists($key, $existingData)) {
                $existingData[$key] = $value;
            }
        }

        File::put($file, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return back()->with('success', "Translations updated successfully.");
    }

    public function update_ai(Request $request, $id)
    {
        $validated = $request->validate([
            'keys' => 'nullable|array|max:100',
            'keys.*' => 'string',
        ]);

        $getOption = get_option('languages');

        if (!isset($getOption[$id])) {
            return back()->withErrors(['error' => 'Language not found in configuration.']);
        }

        $file = base_path('lang/' . $id . '.json');

        if (!file_exists($file)) {
            abort(404, 'Language file not found');
        }
        $currentTranslations = json_decode(File::get($file), true);
        $keysToTranslate = $validated['keys'] ?? null;

        if ($keysToTranslate === null || empty($keysToTranslate)) {

            $totalKeys = count($currentTranslations);
            if ($totalKeys > 100) {
                return back()->withErrors([
                    'error' => "This language file contains {$totalKeys} keys. at a time maximum of 100 keys can be translated."
                ]);
            }
            $keysToTranslate = array_keys($currentTranslations);
        } else {
            $keysToTranslate = array_slice($keysToTranslate, 0, 100);
        }
        $backupDir = base_path('lang/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backupFile = $backupDir . '/' . $id . '_backup.json';
        File::copy($file, $backupFile);

        $systemPrompt = "You are a professional translation engine specializing in software localization.
            CRITICAL RULES:
            1. Return ONLY valid JSON - no markdown, no code fences, no explanations
            2. Keep all keys EXACTLY as provided - never translate keys
            3. Translate ONLY the values into the target language
            4. Preserve placeholder syntax like :name, {count}, %s, {{variable}}
            5. Maintain HTML tags if present (e.g., <strong>, <br>)
            6. Keep special formatting characters (\\n for newlines)
            7. Preserve capitalization style (Title Case, UPPERCASE, etc.)
            8. Maintain punctuation patterns from the source
            9. For technical terms, use standard industry translations
            10. Keep brand names unchanged

            QUALITY STANDARDS:
            - Use natural, native-speaker language
            - Match the formality level of the original
            - Consider cultural context for idioms
            - Maintain consistency across similar phrases";

        $translationsToProcess = array_intersect_key(
            $currentTranslations,
            array_flip($keysToTranslate)
        );

        $jsonToTranslate = json_encode($translationsToProcess, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Translate this JSON file into {$id}: {$getOption[$id]}

        IMPORTANT CONTEXT:
        - This is for a web application interface
        - Keep keys unchanged, translate only values
        - Preserve all placeholders and special syntax

        Now translate the complete JSON file:
        {$jsonToTranslate}

        Remember: Return ONLY valid JSON, no additional text or formatting.";

        try {
            $responseText = Prism::text()
                ->using('gemini', 'gemini-2.0-flash')
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->asText();
        } catch (PrismException $e) {
            throw new SessionException('AI translation failed: ' . $e->getMessage());
        }

        $cleanResponse = preg_replace('/^```json\s*|```$/m', '', trim($responseText->text));
        $cleanResponse = preg_replace('/^```\s*|```$/m', '', $cleanResponse);
        $cleanResponse = trim($cleanResponse);

        $translatedData = json_decode($cleanResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($translatedData)) {
            if (file_exists($backupFile)) {
                File::copy($backupFile, $file);
            }
            return back()->withErrors([
                'error' => 'Failed to parse AI response. Backup has been restored. Error: ' . json_last_error_msg()
            ]);
        }

        $originalKeys = array_keys($translationsToProcess);
        $translatedKeys = array_keys($translatedData);

        if (count(array_diff($originalKeys, $translatedKeys)) > 0) {
            if (file_exists($backupFile)) {
                File::copy($backupFile, $file);
            }
            return back()->withErrors([
                'error' => 'AI translation altered the keys. Backup has been restored.'
            ]);
        }

        $finalData = array_merge($currentTranslations, $translatedData);

        $jsonContent = json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($file, $jsonContent);

        return Inertia::location(route('admin.language.show', $id));
    }

    public function revertAi($id)
    {
        $file = base_path('lang/' . $id . '.json');
        $backupFile = base_path('lang/backups/' . $id . '_backup.json');

        if (!file_exists($backupFile)) {
            return back()->withErrors(['error' => 'No backup found to revert to.']);
        }

        File::copy($backupFile, $file);

        File::delete($backupFile);

        return back()->with('success', 'Translations reverted to backup successfully.');
    }

    public function addKey(Request $request)
    {
        $validated = $request->validate([
            'id' => [
                'required',
                'string',
            ],
            'key' => [
                'required',
                'string',
                'max:255'
            ],
            'value' => 'required|string|max:10000',
        ]);

        $file = base_path('lang/' . $validated['id'] . '.json');

        if (!file_exists($file)) {
            abort(404, 'Language file not found');
        }

        $languages = json_decode(file_get_contents($file), true);

        if (isset($languages[$validated['key']])) {
            return back()->withErrors(['key' => 'This key already exists.']);
        }

        $data = [];
        foreach ($languages as $key => $row) {
            $data[$key] = $row;
        }
        $data[$validated['key']] = $validated['value'];

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($file, $jsonContent);

        return back()->with('success', 'Translation key added successfully.');
    }

    public function destroy($id)
    {
        if ($id === config('app.locale')) {
            return back()->withErrors(['error' => 'Cannot delete the default language.']);
        }

        $languageOption = Option::where('key', 'languages')->first();

        if (!$languageOption) {
            return back()->withErrors(['error' => 'Languages configuration not found.']);
        }

        $languages = $languageOption->value;

        if (!isset($languages[$id])) {
            return back()->withErrors(['error' => 'Language not found.']);
        }

        $data = [];
        foreach ($languages as $key => $row) {
            if ($id != $key) {
                $data[$key] = $row;
            }
        }

        $languageOption->value = $data;
        $languageOption->save();

        $file = base_path('lang/' . $id . '.json');
        if (file_exists($file)) {
            unlink($file);
        }
        $backupFile = base_path('lang/backups/' . $id . '_backup.json');
        if (file_exists($backupFile)) {
            unlink($backupFile);
        }

        Cache::forget('languages');

        return back()->with('success', 'Language deleted successfully.');
    }
}