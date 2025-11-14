<?php

namespace App\Services\Ingestion;

use App\Models\Edge;
use App\Models\Node;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvEdgeImporter
{
    /**
     * @var list<string>
     */
    private const REQUIRED_COLUMNS = ['origin', 'destination', 'avg_lead_time_days'];

    /**
     * @return array{processed:int,created:int,updated:int,errors:list<array<string,string|int>>}
     */
    public function import(Tenant $tenant, UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Invalid CSV upload.');
        }

        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open uploaded file for reading.');
        }

        $header = null;

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeader($row);
                $missing = array_diff(self::REQUIRED_COLUMNS, $header);

                if ($missing !== []) {
                    fclose($handle);

                    return [
                        'processed' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'errors' => [
                            ['row' => 0, 'error' => 'Missing required columns: '.implode(', ', $missing)],
                        ],
                    ];
                }

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $summary['processed']++;

            $data = $this->combineRow($header, $row);

            $error = $this->validateRow($data, $tenant);

            if (is_string($error)) {
                $summary['errors'][] = ['row' => $summary['processed'], 'error' => $error];
                continue;
            }

            [$originNode, $destinationNode] = $error;

            $edge = Edge::firstOrNew([
                'tenant_id' => $tenant->id,
                'origin_node_id' => $originNode->id,
                'destination_node_id' => $destinationNode->id,
            ]);

            $edge->fill([
                'avg_lead_time_days' => (int) $data['avg_lead_time_days'],
                'lead_time_std_days' => isset($data['lead_time_std_days']) && $data['lead_time_std_days'] !== '' ? (int) $data['lead_time_std_days'] : null,
                'volume' => isset($data['volume']) && $data['volume'] !== '' ? (int) $data['volume'] : null,
                'cost_per_unit' => isset($data['cost_per_unit']) && $data['cost_per_unit'] !== '' ? (float) $data['cost_per_unit'] : null,
            ]);

            $isNew = ! $edge->exists;
            $wasDirty = $edge->isDirty();

            $edge->tenant()->associate($tenant);
            $edge->save();

            if ($isNew) {
                $summary['created']++;
            } elseif ($wasDirty) {
                $summary['updated']++;
            }
        }

        fclose($handle);

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $row
     * @return list<Node>|string
     */
    private function validateRow(array $row, Tenant $tenant): array|string
    {
        $origin = $row['origin'] ?? null;
        $destination = $row['destination'] ?? null;

        if (empty($origin) || empty($destination)) {
            return 'Origin and destination are required.';
        }

        if (! isset($row['avg_lead_time_days']) || $row['avg_lead_time_days'] === '') {
            return 'Average lead time is required.';
        }

        if (! is_numeric($row['avg_lead_time_days']) || (int) $row['avg_lead_time_days'] <= 0) {
            return 'Average lead time must be a positive number.';
        }

        foreach (['lead_time_std_days', 'volume'] as $intField) {
            if (isset($row[$intField]) && $row[$intField] !== '' && ! is_numeric($row[$intField])) {
                return ucfirst(str_replace('_', ' ', $intField)).' must be numeric.';
            }
        }

        if (isset($row['cost_per_unit']) && $row['cost_per_unit'] !== '' && ! is_numeric($row['cost_per_unit'])) {
            return 'Cost per unit must be numeric.';
        }

        $nodes = Node::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('name', [$origin, $destination])
            ->get()
            ->keyBy('name');

        $originNode = $nodes->get($origin);
        $destinationNode = $nodes->get($destination);

        if (! $originNode || ! $destinationNode) {
            $missing = [];

            if (! $originNode) {
                $missing[] = "origin '{$origin}'";
            }

            if (! $destinationNode) {
                $missing[] = "destination '{$destination}'";
            }

            return 'Unknown '.implode(' and ', $missing).'.';
        }

        return [$originNode, $destinationNode];
    }

    /**
     * @param  list<string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $row
     * @return array<string, string|null>
     */
    private function combineRow(array $header, array $row): array
    {
        $row = array_pad($row, count($header), null);

        return array_combine($header, array_map(fn ($value) => $value !== null ? trim((string) $value) : null, $row)) ?: [];
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function normalizeHeader(array $row): array
    {
        return array_map(fn ($value) => strtolower(trim($value)), $row);
    }
}
