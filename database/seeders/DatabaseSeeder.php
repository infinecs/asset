<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Category;
use App\Models\Location;
use App\Models\RequestTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $admin = User::create([
            'name' => 'IT Admin',
            'email' => 'admin@itasset.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'IT Department',
            'phone' => '+1-555-0100',
        ]);

        $staff = User::create([
            'name' => 'IT Staff',
            'email' => 'staff@itasset.local',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'department' => 'IT Department',
            'phone' => '+1-555-0101',
        ]);

        $users = collect([
            ['name' => 'Alice Johnson', 'email' => 'alice@itasset.local', 'department' => 'Finance'],
            ['name' => 'Bob Smith', 'email' => 'bob@itasset.local', 'department' => 'HR'],
            ['name' => 'Carol White', 'email' => 'carol@itasset.local', 'department' => 'Marketing'],
            ['name' => 'David Brown', 'email' => 'david@itasset.local', 'department' => 'Operations'],
        ])->map(fn($u) => User::create(array_merge($u, ['password' => Hash::make('password'), 'role' => 'user'])));

        // Categories
        $categories = collect([
            ['name' => 'Laptop', 'description' => 'Portable computers'],
            ['name' => 'Desktop', 'description' => 'Desktop computers and workstations'],
            ['name' => 'Monitor', 'description' => 'Display screens'],
            ['name' => 'Printer', 'description' => 'Printers and scanners'],
            ['name' => 'Network Equipment', 'description' => 'Routers, switches, access points'],
            ['name' => 'Mobile Device', 'description' => 'Smartphones and tablets'],
            ['name' => 'Peripherals', 'description' => 'Keyboards, mice, webcams'],
            ['name' => 'Server', 'description' => 'Server hardware'],
        ])->map(fn($c) => Category::create(array_merge($c, ['slug' => Str::slug($c['name'])])));

        // Locations
        $locations = collect([
            ['name' => 'Main Office - Floor 1', 'building' => 'HQ', 'floor' => '1st'],
            ['name' => 'Main Office - Floor 2', 'building' => 'HQ', 'floor' => '2nd'],
            ['name' => 'Main Office - Floor 3', 'building' => 'HQ', 'floor' => '3rd'],
            ['name' => 'IT Server Room', 'building' => 'HQ', 'floor' => 'Basement', 'room' => 'B01'],
            ['name' => 'Conference Room A', 'building' => 'HQ', 'floor' => '1st', 'room' => '101'],
            ['name' => 'Warehouse', 'building' => 'Warehouse', 'floor' => '1st'],
        ])->map(fn($l) => Location::create($l));

        // Assets
        $assetData = [
            ['name' => 'Dell XPS 15', 'brand' => 'Dell', 'model' => 'XPS 15 9530', 'category' => 0, 'status' => 'in_use', 'user' => 0],
            ['name' => 'MacBook Pro 14"', 'brand' => 'Apple', 'model' => 'MacBook Pro M3', 'category' => 0, 'status' => 'in_use', 'user' => 1],
            ['name' => 'HP EliteDesk 800', 'brand' => 'HP', 'model' => 'EliteDesk 800 G9', 'category' => 1, 'status' => 'in_use', 'user' => 2],
            ['name' => 'Dell U2722D Monitor', 'brand' => 'Dell', 'model' => 'U2722D', 'category' => 2, 'status' => 'available', 'user' => null],
            ['name' => 'HP LaserJet Pro', 'brand' => 'HP', 'model' => 'LaserJet Pro 4001dn', 'category' => 3, 'status' => 'in_use', 'user' => null],
            ['name' => 'Cisco Catalyst Switch', 'brand' => 'Cisco', 'model' => 'Catalyst 2960', 'category' => 4, 'status' => 'in_use', 'user' => null],
            ['name' => 'iPhone 15 Pro', 'brand' => 'Apple', 'model' => 'iPhone 15 Pro', 'category' => 5, 'status' => 'in_use', 'user' => 3],
            ['name' => 'Logitech MX Keys Keyboard', 'brand' => 'Logitech', 'model' => 'MX Keys', 'category' => 6, 'status' => 'available', 'user' => null],
            ['name' => 'Dell PowerEdge R740', 'brand' => 'Dell', 'model' => 'PowerEdge R740', 'category' => 7, 'status' => 'in_use', 'user' => null],
            ['name' => 'Lenovo ThinkPad X1', 'brand' => 'Lenovo', 'model' => 'ThinkPad X1 Carbon', 'category' => 0, 'status' => 'under_maintenance', 'user' => null],
            ['name' => 'Samsung Monitor 27"', 'brand' => 'Samsung', 'model' => 'Odyssey G5', 'category' => 2, 'status' => 'available', 'user' => null],
            ['name' => 'HP Spectre x360', 'brand' => 'HP', 'model' => 'Spectre x360 14', 'category' => 0, 'status' => 'in_use', 'user' => null],
        ];

        $allUsers = $users->prepend($staff)->prepend($admin);

        foreach ($assetData as $i => $data) {
            $count = Asset::count() + 1;
            $tag = 'AST' . now()->format('y') . str_pad($count, 4, '0', STR_PAD_LEFT);
            $asset = Asset::create([
                'asset_tag' => $tag,
                'name' => $data['name'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'serial_number' => strtoupper(Str::random(10)),
                'category_id' => $categories[$data['category']]->id,
                'location_id' => $locations[array_rand([0,1,2,3,4,5])]->id,
                'assigned_to' => $data['user'] !== null ? $allUsers[$data['user']]->id : null,
                'status' => $data['status'],
                'purchase_date' => now()->subMonths(rand(1, 36)),
                'purchase_cost' => rand(200, 5000),
                'warranty_expiry' => now()->addMonths(rand(-3, 24)),
                'last_seen_at' => now()->subMinutes(rand(0, 10080)),
            ]);

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $admin->id,
                'action' => 'created',
                'notes' => 'Asset registered in system',
            ]);
        }

        // Sample tickets
        $ticketTypes = ['new_asset', 'repair', 'replacement', 'return', 'transfer'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $statuses = ['open', 'in_progress', 'resolved', 'open', 'open'];
        $subjects = [
            'Request for new laptop for remote work',
            'Laptop screen flickering issue',
            'Need replacement keyboard',
            'Returning company phone after resignation',
            'Transfer assets to new department',
            'Printer not working on 2nd floor',
            'Request for extra monitor',
            'Server maintenance required',
        ];

        foreach ($subjects as $i => $subject) {
            $year = now()->format('Y');
            $count = RequestTicket::whereYear('created_at', $year)->count() + 1;
            RequestTicket::create([
                'ticket_number' => 'TKT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT),
                'requested_by' => $allUsers[array_rand($allUsers->toArray())]->id,
                'type' => $ticketTypes[$i % count($ticketTypes)],
                'subject' => $subject,
                'description' => 'This is a sample ticket for: ' . $subject . '. Please process this request at your earliest convenience.',
                'priority' => $priorities[$i % count($priorities)],
                'status' => $statuses[$i % count($statuses)],
                'assigned_to' => rand(0, 1) ? $staff->id : null,
            ]);
        }
    }
}
