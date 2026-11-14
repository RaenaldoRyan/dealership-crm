<?php

namespace App\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Sales\Http\Requests\ReportRequest;
use App\Sales\Models\Deal;
use App\Sales\Queries\SalesReport;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Show sales, conversion, and deals by stage.
     */
    public function index(ReportRequest $request, SalesReport $report): Response
    {
        Gate::authorize('viewReports', Deal::class);

        $sales = $report->present($request->range());

        return Inertia::render('reports/Index', [
            'range' => $request->range(),
            'reps' => $sales['reps'],
            'stages' => $sales['stages'],
        ]);
    }

    /**
     * Download the sales table for the same period.
     */
    public function export(ReportRequest $request, SalesReport $report): HttpResponse
    {
        Gate::authorize('viewReports', Deal::class);

        return response($report->csv($request->range()), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sales-report.csv"',
        ]);
    }
}
