<?php

namespace App\Http\Controllers;

use App\Services\GSTRExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GSTReportController extends Controller
{
    public function __construct(private GSTRExportService $exporter) {}

    public function index(Request $request)
    {
        return Inertia::render('Reports/Index');
    }

    public function gstr1(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2017|max:2099',
        ]);

        $data = $this->exporter->gstr1(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        if ($request->format === 'json') {
            return response()->json($data)
                ->header('Content-Disposition', 'attachment; filename="GSTR1_' . $request->month . '_' . $request->year . '.json"');
        }

        return Inertia::render('Reports/GSTR1', ['data' => $data, 'month' => $request->month, 'year' => $request->year]);
    }

    public function gstr3b(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2017|max:2099',
        ]);

        $data = $this->exporter->gstr3b(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        return Inertia::render('Reports/GSTR3B', ['data' => $data, 'month' => $request->month, 'year' => $request->year]);
    }

    public function downloadGSTR1(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2017|max:2099',
        ]);

        $json = $this->exporter->exportGSTR1Json(
            $request->user()->tenant,
            (int) $request->month,
            (int) $request->year
        );

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="GSTR1_' . $request->month . '_' . $request->year . '.json"',
        ]);
    }
}
