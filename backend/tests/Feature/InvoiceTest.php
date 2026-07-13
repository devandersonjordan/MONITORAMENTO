<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->for($this->company)->create(['role' => 'admin']);
    }

    public function test_can_list_invoices(): void
    {
        $client = User::factory()->for($this->company)->client()->create();
        Invoice::factory()->count(3)->create([
            'client_id' => $client->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/invoices');

        $response->assertOk();
    }

    public function test_can_view_invoice(): void
    {
        $client = User::factory()->for($this->company)->client()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id);
    }

    public function test_can_filter_invoices_by_client(): void
    {
        $client = User::factory()->for($this->company)->client()->create();
        Invoice::factory()->count(2)->create([
            'client_id' => $client->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/invoices?client_id={$client->id}");

        $response->assertOk();
    }
}
