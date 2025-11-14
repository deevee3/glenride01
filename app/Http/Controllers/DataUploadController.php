<?php

namespace App\Http\Controllers;

use App\Http\Requests\Uploads\EdgeUploadRequest;
use App\Http\Requests\Uploads\NodeUploadRequest;
use App\Services\Ingestion\CsvEdgeImporter;
use App\Services\Ingestion\CsvNodeImporter;
use Illuminate\Http\JsonResponse;

class DataUploadController extends Controller
{
    public function __construct(
        private readonly CsvNodeImporter $nodeImporter,
        private readonly CsvEdgeImporter $edgeImporter,
    ) {
    }

    public function uploadNodes(NodeUploadRequest $request): JsonResponse
    {
        $result = $this->nodeImporter->import($request->user()->tenant, $request->file('file'));

        if ($result['errors'] !== []) {
            return response()->json([
                'message' => 'Nodes CSV processed with errors.',
                'errors' => $result['errors'],
                'summary' => $result,
            ], 422);
        }

        return response()->json([
            'message' => 'Nodes CSV processed successfully.',
            'summary' => $result,
        ]);
    }

    public function uploadEdges(EdgeUploadRequest $request): JsonResponse
    {
        $result = $this->edgeImporter->import($request->user()->tenant, $request->file('file'));

        if ($result['errors'] !== []) {
            return response()->json([
                'message' => 'Edges CSV processed with errors.',
                'errors' => $result['errors'],
                'summary' => $result,
            ], 422);
        }

        return response()->json([
            'message' => 'Edges CSV processed successfully.',
            'summary' => $result,
        ]);
    }
}
