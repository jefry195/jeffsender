<?php

namespace Modules\AiTraining\App\Http\Controllers;

use App\Exceptions\SessionException;
use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Imports\DatasetImport;
use App\Imports\DatasetImportCsv;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AiTraining\App\Models\AiTraining;
use Modules\AiTraining\App\Models\AiTrainingCredential;
use Modules\AiTraining\App\Services\FineTuningProvider;

class AiTrainingController extends Controller
{
    use Uploader;

    public function index()
    {
        PageHeader::set()
            ->title(__('Ai Trainings'))
            ->addModal('Import Dataset', 'importDatasetModal', 'bx:upload')
            ->addLink('Add Data', route('user.aitraining.ai-training.create'), 'bx:plus');

        $aiTrainingCredentials = AiTrainingCredential::where('user_id', activeWorkspaceOwnerId())->get();

        $aiModules = collect([
            'openai' => [
                'config' => [
                    'schema' => config('aitraining.providers.0.schema', []),
                    'info' => config('aitraining.providers.0.info', []),
                ],
                'dropdown_items' => [],
            ],
        ]);

        $providerConfig = $aiModules->map(fn ($module) => $module['config'])->toArray();

        $providerConfigData = collect($providerConfig)->map(function ($config) {
            return $config['schema'];
        })->toArray();

        $providerConfigSchema = [];

        foreach ($providerConfigData as $provider => $defaultConfig) {
            $credential = $aiTrainingCredentials->firstWhere('provider', $provider);

            if ($credential) {
                $metaData = $credential->meta;
                $providerConfigSchema[$provider] = array_merge($defaultConfig, $metaData);
            } else {
                $providerConfigSchema[$provider] = $defaultConfig;
            }
        }
        $demoDatasets = [
            'json' => asset('assets/dataset.json'),
            'csv' => asset('assets/dataset.csv'),
        ];

        return Inertia::render('AiTraining/Index', [
            'providerConfigSchema' => $providerConfigSchema,
            'providerConfig' => $providerConfig,
            'aiTrainingCredentials' => $aiTrainingCredentials,
            'aiModules' => $aiModules,
            'demoDatasets' => $demoDatasets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        PageHeader::set(title: __('Ai Trainings Create'));

        $providers = [
            'openai',
        ];

        return Inertia::render('AiTraining/Create', [
            'providers' => $providers,
        ]);
    }

    private function createFineTuningJob($aiTraining, $provider, $title)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        $credentials = AiTrainingCredential::where('user_id', activeWorkspaceOwnerId())->where('provider', $provider)->first();
        if (! $credentials) {
            return back()->with('danger', 'Ai credentials not found.');
        }

        $dataset = File::get(public_path(parse_url($aiTraining->dataset, PHP_URL_PATH)));
        $dataset = json_decode($dataset, true);

        $fineTuningProvider = new FineTuningProvider($provider);
        if ($credentials) {
            $response = $fineTuningProvider->createFineTuningJob($credentials, $dataset, $title);
            $aiTraining->update([
                'status' => $response['status'] ?? 'pending',
                'meta' => $response,
                'model_name' => $response['model_name'],
                'model_id' => $response['model_id'],
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'dataset' => 'required|array',
            'dataset.*.question' => 'required|string',
            'dataset.*.answer' => 'required|string',
        ], [
            'dataset.*.question.required' => 'The question field is required.',
            'dataset.*.answer.required' => 'The answer field is required.',
        ]);

        $dataset = $this->saveJsonFile($request, 'dataset');
        $aiTraining = AiTraining::create([
            'title' => $validated['title'],
            'provider' => $validated['provider'],
            'user_id' => activeWorkspaceOwnerId(),
            'status' => 'pending',
            'dataset' => $dataset,
        ]);

        $this->createFineTuningJob($aiTraining, $validated['provider'], $validated['title']);

        return redirect()->route('user.aitraining.ai-training.show', $aiTraining->provider)->with('success', 'Ai training created.');
    }

    public function storeCredentials(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }
        $data = $request->all();
        foreach ($data as $key => $value) {
            AiTrainingCredential::updateOrCreate(
                ['user_id' => activeWorkspaceOwnerId(), 'provider' => $key],
                ['meta' => $value]
            );
        }

        return back()->with('success', 'Ai credentials saved.');
    }

    /**
     * Display the specified resource.
     */
    public function show($provider)
    {
        PageHeader::set(title: __('Ai Trainings Data'))
            ->addLink('Add New', route('user.aitraining.ai-training.create'), 'bx:plus')
            ->addLink('Sync Fine-tuning', route('user.aitraining.ai-training.sync', $provider), 'bx:refresh', animate: true);
        $aiTrainings = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->where('provider', $provider)->paginate();

        return Inertia::render('AiTraining/Show', [
            'aiTrainings' => $aiTrainings,
            'provider' => $provider,
            'isDev' => env('APP_ENV') === 'local',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function checkStatus($id)
    {

        $aiTraining = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->findOrFail($id);
        $fineTuningProvider = new FineTuningProvider($aiTraining->provider);
        $credentials = AiTrainingCredential::query()->where('user_id', activeWorkspaceOwnerId())->where('provider', $aiTraining->provider)->first();
        $fineTuningStatus = $fineTuningProvider
            ->getFineTuningStatus($credentials, $aiTraining);

        return back()->with('success', 'Ai training status updated to '.$fineTuningStatus['status']);
    }

    public function update(Request $request, $id)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        $aiTraining = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->where('id', $id)->firstOrFail();
        if ($aiTraining->status !== 'pending') {
            throw new SessionException('Ai training dataset cannot be edited.');
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'dataset' => 'required|array',
            'dataset.*.question' => 'required|string',
            'dataset.*.answer' => 'required|string',
        ], [
            'dataset.*.question.required' => 'The question field is required.',
            'dataset.*.answer.required' => 'The answer field is required.',
        ]);

        $this->removeFile($aiTraining->dataset);
        $dataset = $this->saveJsonFile($request, 'dataset');
        $aiTraining->update([
            'title' => $request->title,
            'dataset' => $dataset,
        ]);

        return back()->with('success', 'Ai training updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        $aiTraining = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->where('id', $id)->firstOrFail();

        $fineTuningProvider = new FineTuningProvider($aiTraining->provider);
        $credentials = AiTrainingCredential::query()->where('user_id', activeWorkspaceOwnerId())->where('provider', $aiTraining->provider)->first();
        $fineTuningProvider->destroyFineTunedModel($credentials, $aiTraining->model_id);
        $aiTraining->delete();

        return back()->with('danger', 'Ai training deleted.');
    }

    public function destroyCredentials($provider)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        AiTrainingCredential::query()->where('user_id', activeWorkspaceOwnerId())->where('provider', $provider)->delete();

        return Inertia::location(route('user.aitraining.ai-training.index'));
    }

    public function destroyRecord($id)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        $aiTraining = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->where('id', $id)->firstOrFail();
        $aiTraining->delete();

        return back()->with('danger', 'Ai training record deleted.');
    }

    public function importDataset(Request $request)
    {
        if (env('DEMO_MODE')) {
            return back()->with('danger', __('Permission disabled for demo !'));
        }

        $request->validate([
            'dataset' => 'required|file|mimes:json,csv,xlsx',
            'provider' => 'required|string',
            'title' => 'required|string',
            'file_type' => 'required|string|in:json,csv',
        ]);
        if ($request->hasFile('dataset')) {
            $fileExtension = $request->dataset->extension();
            if ($fileExtension == 'json' && ! in_array($request->file_type, ['json'])) {
                return back()->with('danger', 'Invalid file type. Select json file.');
            }
            if (($fileExtension == 'csv' || $fileExtension == 'xlsx') && ! in_array($request->file_type, ['csv', 'xlsx'])) {
                return back()->with('danger', 'Invalid file type. Select csv file.');
            }
        }
        if ($request->hasFile('dataset') && $request->file_type == 'json') {
            $importer = new DatasetImport($request->provider);
            $result = $importer->import($request);

            $this->createFineTuningJob($result['aiTraining'], $request->provider, $request->title);
            if ($result['success']) {
                return back()->with('success', 'Dataset imported successfully');
            } else {
                return back()->with('danger', $result['errors']);
            }
        }
        if ($request->hasFile('dataset') && $request->file_type == 'csv') {
            $uploadedData = $this->saveFile($request, 'dataset');
            $parsedPath = '/'.parse_url($uploadedData, PHP_URL_PATH);
            try {
                $import = new DatasetImportCsv($request->provider, $request);
                Excel::import($import, $parsedPath);
                $this->createFineTuningJob($import->aiTraining, $request->provider, $request->title);
                $this->removeFile($uploadedData);

                return redirect()->back()->with('success', 'Dataset imported successfully.');
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();

                return redirect()->back()->withErrors($failures)->withInput();
            }
        }
    }

    public function syncFineTuning($provider)
    {
        $fineTuningProvider = new FineTuningProvider($provider);
        $credentials = AiTrainingCredential::where('user_id', activeWorkspaceOwnerId())->where('provider', $provider)->first();
        $fineTuningProvider->getFineTuningJobs($credentials);

        return back()->with('success', 'Fine-tuned models synced successfully.');
    }

    public function testPrompt($id)
    {
        $aiTraining = AiTraining::where('user_id', activeWorkspaceOwnerId())
            ->findOrFail($id);
        $fineTuningProvider = new FineTuningProvider($aiTraining->provider);
        $credentials = AiTrainingCredential::where('user_id', activeWorkspaceOwnerId())->where('provider', $aiTraining->provider)->first();
        $prompt = $fineTuningProvider->generatePrompt('user', 'Hello!');
        $fineTuningProvider
            ->getFineTunedCompletion($credentials, $aiTraining->model_id, [$prompt]);
        if ($fineTuningProvider->compilationResponse()) {
            return back()->with('success', $fineTuningProvider->compilationResponse());
        }

        return back()->with('danger', 'Failed to generate prompt.');
    }
}
