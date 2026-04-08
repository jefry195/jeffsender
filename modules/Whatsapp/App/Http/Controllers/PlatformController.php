<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Helpers\PageHeader;
use App\Helpers\PlanPerks;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformConfigRequest;
use App\Models\Platform;
use App\Services\AutoReplyService;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Whatsapp\App\Services\WhatsappClient;
use Nwidart\Modules\Facades\Module;

class PlatformController extends Controller
{
    public function index()
    {
        $moduleName = 'Whatsapp';
        $moduleNameLower = strtolower($moduleName);

        $query = activeWorkspaceOwner()->platforms()->where('module', $moduleNameLower);

        PageHeader::set(
            title: "$moduleName Platforms",
            overviews: [
                [
                    'icon' => 'bx:grid-alt',
                    'title' => __('Total Devices'),
                    'value' => $query->clone()->count(),
                ],
                [
                    'title' => __('Connected Devices'),
                    'value' => $query->clone()->where('meta->webhook_connected', true)->count(),
                    'icon' => 'bx:check-circle',
                ],
                [
                    'title' => __('Max Devices Limit'),
                    'value' => PlanPerks::planValue('devices'),
                    'icon' => 'bx:sort-up',
                ],
                [
                    'icon' => 'bx:group',
                    'title' => __('Total Contacts'),
                    'value' => activeWorkspaceOwner()->customers()->whatsapp()->count(),
                ],
            ]
        )->when(activeWorkspaceOwnerId() == Auth::id(), function ($pageHeader) use ($moduleNameLower) {
            $pageHeader->addLink(__('Add New Device'), route("user.$moduleNameLower.platforms.create"), 'bx:plus');
        });

        $platforms = $query->clone()->filterOn(['name', 'status'])->paginate()
            ->through(function ($platform) {
                $platform->webhook_url = route('api.whatsapp.webhook', ['uuid' => $platform->uuid]);

                return $platform;
            });

        $autoReplyServices = AutoReplyService::getAutoReplyServices('whatsapp');

        return Inertia::render('Platforms/Index', [
            'platforms' => $platforms,
            'autoReplyServices' => $autoReplyServices,
        ]);
    }

    public function create()
    {
        abort_unless(activeWorkspaceOwnerId() == Auth::id(), 403, 'You do not have permission to perform this action in this workspace.');
        validateWorkspacePlan('devices');

        if (Module::has('WhatsappES') && Module::isEnabled('WhatsappES') && Route::has('user.whatsapp-es.platforms.create')) {
            return Inertia::location(route('user.whatsapp-es.platforms.create'));
        }

        PageHeader::set()->title('Platforms Create')->addBackLink(route('user.whatsapp.platforms.index'));

        return Inertia::render('Platforms/Create');
    }

    public function store(Request $request)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        abort_unless(activeWorkspaceOwnerId() == Auth::id(), 403, __('You do not have permission to perform this action in this workspace.'));
        validateWorkspacePlan('devices');

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'phone_number_id' => 'required|numeric|unique:platforms,uuid',
            'business_account_id' => 'required|numeric',
            'access_token' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            $res = WhatsappClient::make($validated['access_token'], $validated['business_account_id'])->getTemplates($validated['business_account_id']);

            if (! $res->successful()) {
                throw new \Exception($res->collect('error')->first());
            }

            $platform = Platform::create([
                'module' => 'whatsapp',
                'owner_id' => activeWorkspaceOwnerId(),

                'name' => $validated['name'],
                'uuid' => $validated['phone_number_id'],

                'access_token' => $validated['access_token'],
                'access_token_expire_at' => now()->addDay(),

                'refresh_token' => null,
                'refresh_token_expire_at' => null,
                'meta' => Platform::defaultMeta([
                    'phone_number_id' => $validated['phone_number_id'],
                    'business_account_id' => $validated['business_account_id'],
                    'webhook_connected' => false,
                    'signup_method' => 'manual',
                ]),
            ]);

            DB::commit();

            return response()->json([
                'platform' => $platform,
                'app_url' => config('app.url'),
                'message' => __('Platform has been created successfully'),
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();

            return response()->json([
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    public function update(PlatformConfigRequest $request, Platform $platform)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        $validated = $request->except(['access_token', 'name']);

        $meta = $platform->meta ?? Platform::defaultMeta();

        $platform->update([
            'access_token' => $request->input('access_token'),
            'name' => $request->input('name'),
            'meta' => [
                ...$meta,
                ...$validated,
            ],
        ]);

        return back()->with('success', __('Platform configuration has been updated successfully'));
    }

    public function destroy(Platform $platform)
    {
        if (env('DEMO_MODE') && auth()->user()->id == 3) {
            return back()->with('danger', __('Permission disabled for demo account please create a test account..!'));
        }
        if (activeWorkspaceOwnerId() != Auth::id()) {
            return back()->with('danger', __('You do not have permission to delete this platform'));
        }

        $platform->delete();

        return back()->with('success', __('Platform has been removed successfully'));
    }

    protected function syncTemplates()
    {
        $platforms = Auth::user()->platforms()->whatsapp()->get();
        foreach ($platforms as $platform) {
            $platform->syncTemplates();
        }
    }

    public function logs(Platform $platform)
    {
        PageHeader::set()->title('Platforms Logs')->addBackLink(route('user.whatsapp.platforms.index'));
        $logs = $platform->logs()->paginate();

        return Inertia::render('Platforms/Logs', [
            'logs' => $logs,
            'platform' => $platform,
        ]);
    }

    public function show($platformUuid, $conversation_id = null)
    {
        $platform = Platform::where('uuid', $platformUuid)->firstOrFail();
        PageHeader::set(__('WhatsApp Chats'));

        $chatService = new ChatService('whatsapp', $platform);
        $languages = json_decode(file_get_contents(base_path('database/json/languages.json')), true);
        $languages = array_values(array_map(function ($language) {
            return [
                'id' => $language['id'],
                'name' => $language['name'],
            ];
        }, $languages));

        $moduleFeatures = Module::find('Whatsapp')->get('features', [
            'voice_messages' => false,
        ]);

        return Inertia::render('Platforms/Show', [
            'id' => $conversation_id,
            'platform' => $platform,
            'conversations' => $chatService->conversations(),
            'chat_templates' => $chatService->templates(),
            'quick_replies' => $chatService->quickReplyTemplates(),
            'badges' => $chatService->badges(),
            'languages' => $languages,
            'module_features' => $moduleFeatures,
        ]);
    }

    public function showConversation($platform_uuid, $conversation_id)
    {
        $platform = activeWorkspaceOwner()->platforms()->where([
            'module' => 'whatsapp',
            'uuid' => $platform_uuid,
        ])->firstOrFail();

        $conversation = activeWorkspaceOwner()
            ->conversations()
            ->where([
                'module' => 'whatsapp',
                'platform_id' => $platform->id,
                'id' => $conversation_id,
            ])
            ->firstOrFail();

        if ($conversation->messages()->unRead()->exists()) {
            $conversation->messages()->update(['status' => 'read']);
            $conversation->touch();
        }

        $customerName = $conversation->customer?->name ?? 'NA';

        PageHeader::set(__("Chat with {$customerName}"))->addModal(__('AI Tools'), 'aiModal');

        return $this->show($platform->uuid, $conversation->id);
    }
}
