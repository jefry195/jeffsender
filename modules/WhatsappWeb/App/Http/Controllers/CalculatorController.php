<?php

namespace Modules\WhatsappWeb\App\Http\Controllers;

use Inertia\Inertia;
use App\Helpers\PageHeader;
use App\Http\Controllers\Controller;
use App\Services\BoxCalculatorService;

class CalculatorController extends Controller
{
    /**
     * Show the web-based box calculator page.
     */
    public function index()
    {
        PageHeader::set('Kalkulator Kemasan Box Custom');

        $workspace = auth()->user()?->getCurrentWorkspace();
        $workspaceName = $workspace?->name ?? '';
        $isDoorenz = (str_contains(strtolower($workspaceName), 'doorenz') || str_contains(strtolower($workspaceName), "dooren'z"));

        return Inertia::render('Calculator/Index', [
            'productTypes'    => BoxCalculatorService::getProductTypes($isDoorenz),
            'bahanOptions'    => BoxCalculatorService::getBahanOptions($isDoorenz),
            'laminasiOptions' => BoxCalculatorService::getLaminasiOptions(),
            'isDoorenz'       => $isDoorenz,
        ]);
    }

    /**
     * Handle AJAX calculation request from the frontend.
     */
    public function calculate(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'type'       => 'required|string',
            'dimensions' => 'required|array',
            'qty'        => 'required|integer|min:1',
            'material'   => 'required|string',
            'laminasi'   => 'required|string',
            'warna'      => 'nullable|string',
        ]);

        try {
            $workspace = auth()->user()?->getCurrentWorkspace();
            $workspaceName = $workspace?->name ?? '';
            $isDoorenz = (str_contains(strtolower($workspaceName), 'doorenz') || str_contains(strtolower($workspaceName), "dooren'z"));

            // Map warna string to integer (full = 4)
            $warnaStr = $validated['warna'] ?? 'full';
            $warna = match ($warnaStr) {
                '1'    => 1,
                '2'    => 2,
                '3'    => 3,
                default => 4, // 'full' atau apapun
            };

            $result = BoxCalculatorService::calculatePrice(
                $validated['type'],
                $validated['dimensions'],
                $validated['qty'],
                $validated['material'],
                $validated['laminasi'],
                $warna,
                $isDoorenz
            );

            // Also include flat size for visualization
            $flatSize = BoxCalculatorService::calculateFlatSize(
                $validated['type'],
                $validated['dimensions'],
                $validated['material'],
                $isDoorenz
            );

            return response()->json([
                'success'   => true,
                'result'    => $result,
                'flat_size' => $flatSize,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung: ' . $e->getMessage(),
            ], 422);
        }
    }
}
