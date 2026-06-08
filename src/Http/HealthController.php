<?php

namespace Webrek\HealthUi\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webrek\HealthUi\HealthChecker;

class HealthController
{
    public function __invoke(Request $request, HealthChecker $checker): JsonResponse|Response
    {
        $report = $checker->run();
        $code = $report->isHealthy() ? 200 : 503;

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json($report->toArray(), $code);
        }

        return response()->view('health-ui::status', ['report' => $report], $code);
    }
}
