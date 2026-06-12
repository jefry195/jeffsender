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

        return Inertia::render('Calculator/Index', [
            'productTypes'    => BoxCalculatorService::getProductTypes(),
            'bahanOptions'    => BoxCalculatorService::getBahanOptions(),
            'laminasiOptions' => BoxCalculatorService::getLaminasiOptions(),
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
        ]);

        try {
            $result = BoxCalculatorService::calculatePrice(
                $validated['type'],
                $validated['dimensions'],
                $validated['qty'],
                $validated['material'],
                $validated['laminasi']
            );

            // Also include flat size for visualization
            $flatSize = BoxCalculatorService::calculateFlatSize(
                $validated['type'],
                $validated['dimensions']
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
