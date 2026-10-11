<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Customer;
use App\Models\ServiceLocation;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Services\EstimateService;
use App\Services\InvoiceService;
use App\Services\NumberingService;
use App\Services\WorkOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'maya@harborline.example'],
            [
                'name' => 'Maya Chen',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $team = Team::query()->firstOrCreate(
            ['slug' => 'harborline-field-services'],
            [
                'name' => 'Harborline Field Services',
                'is_personal' => false,
            ],
        );

        if (! $owner->belongsToTeam($team)) {
            $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        }

        $owner->switchTeam($team);

        $roster = [
            ['name' => 'Jordan Hale', 'email' => 'jordan@harborline.example', 'role' => TeamRole::Dispatcher],
            ['name' => 'Priya Shah', 'email' => 'priya@harborline.example', 'role' => TeamRole::Technician],
            ['name' => 'Luis Ortega', 'email' => 'luis@harborline.example', 'role' => TeamRole::Technician],
            ['name' => 'Elena Brooks', 'email' => 'elena@harborline.example', 'role' => TeamRole::Accountant],
            ['name' => 'Sam Rivera', 'email' => 'sam@harborline.example', 'role' => TeamRole::Sales],
        ];

        $users = ['owner' => $owner];

        foreach ($roster as $row) {
            $user = User::query()->firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->belongsToTeam($team)) {
                $team->members()->attach($user, ['role' => $row['role']->value]);
            }

            $user->switchTeam($team);
            $users[strtolower(explode(' ', $row['name'])[0])] = $user;
        }

        TeamSetting::forTeam($team)->update([
            'legal_name' => 'Harborline Field Services LLC',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'tax_label' => 'Sales tax',
            'default_tax_rate' => '8.250',
            'working_hours_start' => 8,
            'working_hours_end' => 17,
            'default_payment_terms_days' => 14,
            'payment_instructions' => 'Mail checks to Harborline Field Services, 18 Pier Street, Harborline, NY 10013. Demo records only.',
        ]);

        $maple = $this->customer($team, [
            'name' => 'Avery Kim',
            'company_name' => 'Maple Court Residences',
            'email' => 'avery.kim@maplecourt.example',
            'phone' => '555-0142',
            'city' => 'Harborline',
            'address' => '220 Maple Court',
        ]);
        $clinic = $this->customer($team, [
            'name' => 'Dr. Naomi Ellis',
            'company_name' => 'Riverview Clinic',
            'email' => 'operations@riverviewclinic.example',
            'phone' => '555-0177',
            'city' => 'Riverview',
            'address' => '44 Riverwalk Drive',
        ]);
        $bakery = $this->customer($team, [
            'name' => 'Theo Lang',
            'company_name' => 'Northglass Bakery',
            'email' => 'theo@northglass.example',
            'phone' => '555-0190',
            'city' => 'Northglass',
            'address' => '9 Oven Lane',
        ]);
        $home = $this->customer($team, [
            'name' => 'Casey Whitaker',
            'company_name' => null,
            'email' => 'casey.whitaker@example.net',
            'phone' => '555-0118',
            'city' => 'Harborline',
            'address' => '71 Cedar Slope',
        ]);

        $numbering = app(NumberingService::class);
        $estimates = app(EstimateService::class);
        $workOrders = app(WorkOrderService::class);
        $invoices = app(InvoiceService::class);

        if (! ServiceRequest::query()->where('team_id', $team->id)->exists()) {
            ServiceRequest::query()->create([
                'team_id' => $team->id,
                'customer_id' => $home->id,
                'service_location_id' => $home->locations()->first()?->id,
                'status' => 'new',
                'priority' => 'high',
                'source' => 'public_form',
                'contact_name' => 'Casey Whitaker',
                'contact_email' => 'casey.whitaker@example.net',
                'contact_phone' => '555-0118',
                'subject' => 'No heat on the second floor',
                'description' => 'Furnace cycles but upstairs rooms stay cold. Fictional demo request.',
                'reference' => $numbering->next($team, 'request'),
            ]);
        }

        $estimate = $estimates->create($team, $owner, [
            'customer_id' => $clinic->id,
            'service_location_id' => $clinic->locations()->first()?->id,
            'currency' => 'USD',
            'tax_rate' => '8.25',
            'discount_amount' => '0',
            'notes' => 'Fictional clinic rooftop unit quote.',
            'customer_notes' => 'Work to be scheduled after hours.',
        ], [
            ['description' => 'Rooftop RTU diagnostic', 'quantity' => 1, 'unit_price' => 185, 'tax_rate' => 8.25],
            ['description' => 'Condenser fan motor', 'quantity' => 1, 'unit_price' => 420, 'tax_rate' => 8.25],
        ]);
        $estimates->send($estimate, $users['sam']);

        $approved = $estimates->create($team, $owner, [
            'customer_id' => $maple->id,
            'service_location_id' => $maple->locations()->first()?->id,
            'currency' => 'USD',
            'tax_rate' => '8.25',
            'notes' => 'Lobby boiler tune-up.',
        ], [
            ['description' => 'Boiler inspection and flush', 'quantity' => 1, 'unit_price' => 640, 'tax_rate' => 8.25],
        ]);
        $estimates->send($approved, $users['sam']);
        $estimates->approve($approved, $owner, 'Avery Kim');

        $completedJob = $workOrders->createFromEstimate($approved, $owner, [
            'title' => 'Lobby boiler service',
            'priority' => 'normal',
            'technician_ids' => [$users['priya']->id],
            'checklist' => ['Lock out energy', 'Flush and inspect', 'Restore heat'],
        ]);
        $workOrders->schedule(
            $completedJob,
            $users['jordan'],
            Carbon::now()->subDays(2)->setTime(9, 0),
            Carbon::now()->subDays(2)->setTime(12, 0),
            [$users['priya']->id],
        );
        $workOrders->start($completedJob, $users['priya']);
        $workOrders->complete($completedJob, $users['priya'], 'Avery Kim');
        $invoice = $invoices->createFromWorkOrder($completedJob, $users['elena']);
        $invoices->recordPayment($invoice, $users['elena'], [
            'amount' => '200.00',
            'method' => 'check',
            'reference' => 'CHK-4401',
            'idempotency_key' => 'demo-chk-4401',
        ]);

        $scheduled = $workOrders->create($team, $owner, [
            'customer_id' => $bakery->id,
            'service_location_id' => $bakery->locations()->first()?->id,
            'title' => 'Walk-in cooler leak check',
            'description' => 'Fictional bakery refrigeration call.',
            'priority' => 'high',
            'scheduled_start_at' => Carbon::now()->addDay()->setTime(8, 0),
            'scheduled_end_at' => Carbon::now()->addDay()->setTime(10, 0),
        ], [
            ['description' => 'Leak search and dye', 'quantity' => 1, 'unit_price' => 240, 'tax_rate' => 8.25],
        ], [$users['luis']->id], ['Confirm temperatures', 'Inspect drain']);

        $workOrders->schedule(
            $scheduled,
            $users['jordan'],
            Carbon::parse($scheduled->scheduled_start_at),
            Carbon::parse($scheduled->scheduled_end_at),
            [$users['luis']->id],
        );

        $inProgress = $workOrders->create($team, $owner, [
            'customer_id' => $home->id,
            'service_location_id' => $home->locations()->first()?->id,
            'title' => 'Second-floor heat call',
            'description' => 'Linked to the fictional Whitaker request.',
            'priority' => 'urgent',
            'service_request_id' => ServiceRequest::query()->where('team_id', $team->id)->latest('id')->value('id'),
        ], [
            ['description' => 'Furnace diagnostic', 'quantity' => 1, 'unit_price' => 129, 'tax_rate' => 8.25],
        ], [$users['priya']->id], ['Verify thermostat', 'Check filter']);

        $workOrders->schedule(
            $inProgress,
            $users['jordan'],
            Carbon::now()->setTime(13, 0),
            Carbon::now()->setTime(15, 0),
            [$users['priya']->id],
        );
        $workOrders->start($inProgress, $users['priya']);
    }

    /**
     * @param  array{name: string, company_name: ?string, email: string, phone: string, city: string, address: string}  $data
     */
    private function customer(Team $team, array $data): Customer
    {
        $customer = Customer::query()->firstOrCreate(
            ['team_id' => $team->id, 'email' => $data['email']],
            [
                'name' => $data['name'],
                'company_name' => $data['company_name'],
                'phone' => $data['phone'],
                'status' => 'active',
                'billing_address_line1' => $data['address'],
                'billing_city' => $data['city'],
                'billing_region' => 'NY',
                'billing_postal_code' => '10013',
                'billing_country' => 'US',
                'internal_notes' => 'Fictional demo account. Not a real customer.',
            ],
        );

        ServiceLocation::query()->firstOrCreate(
            ['team_id' => $team->id, 'customer_id' => $customer->id, 'label' => 'Primary'],
            [
                'address_line1' => $data['address'],
                'city' => $data['city'],
                'region' => 'NY',
                'postal_code' => '10013',
                'country' => 'US',
                'is_primary' => true,
            ],
        );

        return $customer->fresh('locations');
    }
}
