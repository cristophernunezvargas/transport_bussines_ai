<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportDriverWorklogsRequest;
use App\Models\User;
use App\Services\Integrations\DriverWorklogImporter;
use Illuminate\Http\JsonResponse;

/**
 * Receives the daily driver hours n8n collects and folds them into the
 * weekly `driver_worklogs` (see DriverWorklogImporter).
 */
class DriverWorklogImportController extends Controller
{
    public function __invoke(ImportDriverWorklogsRequest $request, DriverWorklogImporter $importer): JsonResponse
    {
        $author = User::query()->where('email', config('integrations.n8n.user_email'))->first();

        abort_if($author === null, 503, 'The integration user is not configured (N8N_USER_EMAIL).');

        /** @var list<array{driver_name: string, work_date: string, hours: int|float|string}> $entries */
        $entries = $request->validated('entries');

        return response()->json($importer->import($entries, $author));
    }
}
