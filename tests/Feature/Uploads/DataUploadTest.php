<?php

use App\Models\Node;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;

it('allows tenants to upload a valid nodes csv', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    $csv = <<<CSV
    name,type,location,capacity
    Port Alpha,port,Los Angeles,500
    Port Beta,port,New York,1200
    CSV;

    $file = UploadedFile::fake()->createWithContent('nodes.csv', trim($csv));

    $response = $this->actingAs($user)->post(route('uploads.nodes'), [
        'file' => $file,
    ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Nodes CSV processed successfully.')
            ->where('summary.processed', 2)
            ->where('summary.created', 2)
            ->where('summary.updated', 0)
            ->has('summary.errors', fn ($errors) => $errors === [])
        );

    $this->assertDatabaseHas('nodes', [
        'tenant_id' => $tenant->id,
        'name' => 'Port Alpha',
    ]);
});

it('returns validation errors when nodes csv rows are invalid', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    $csv = <<<CSV
    name,capacity
    ,not-a-number
    CSV;

    $file = UploadedFile::fake()->createWithContent('nodes.csv', trim($csv));

    $response = $this->actingAs($user)->post(route('uploads.nodes'), [
        'file' => $file,
    ]);

    $response
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Nodes CSV processed with errors.')
            ->where('summary.processed', 1)
            ->where('summary.created', 0)
            ->where('summary.updated', 0)
            ->where('errors.0.row', 1)
            ->where('errors.0.error', 'Name is required.')
        );

    $this->assertDatabaseCount('nodes', 0);
});

it('allows tenants to upload a valid edges csv', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    $origin = Node::factory()->forTenant($tenant)->create(['name' => 'Origin Node']);
    $destination = Node::factory()->forTenant($tenant)->create(['name' => 'Destination Node']);

    $csv = <<<CSV
    origin,destination,avg_lead_time_days,lead_time_std_days,volume,cost_per_unit
    {$origin->name},{$destination->name},5,1,200,3.5
    CSV;

    $file = UploadedFile::fake()->createWithContent('edges.csv', trim($csv));

    $response = $this->actingAs($user)->post(route('uploads.edges'), [
        'file' => $file,
    ]);

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Edges CSV processed successfully.')
            ->where('summary.processed', 1)
            ->where('summary.created', 1)
            ->where('summary.updated', 0)
        );

    $this->assertDatabaseHas('edges', [
        'tenant_id' => $tenant->id,
        'origin_node_id' => $origin->id,
        'destination_node_id' => $destination->id,
    ]);
});

it('returns validation errors when edges csv references unknown nodes', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();

    $csv = <<<CSV
    origin,destination,avg_lead_time_days
    Missing,Unknown,4
    CSV;

    $file = UploadedFile::fake()->createWithContent('edges.csv', trim($csv));

    $response = $this->actingAs($user)->post(route('uploads.edges'), [
        'file' => $file,
    ]);

    $response
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Edges CSV processed with errors.')
            ->where('summary.processed', 1)
            ->where('summary.created', 0)
            ->where('summary.updated', 0)
            ->where('errors.0.row', 1)
            ->where('errors.0.error', "Unknown origin 'Missing' and destination 'Unknown'.")
        );

    $this->assertDatabaseCount('edges', 0);
});
