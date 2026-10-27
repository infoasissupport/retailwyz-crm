<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ExpenseCategory;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Reference data every installation needs: a main branch, common tax rates,
 * units, expense categories and the first Super Admin. Idempotent.
 */
class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Branch']);

        foreach ([['GST 0%', 'GST0', 0], ['GST 5%', 'GST5', 5], ['GST 12%', 'GST12', 12], ['GST 18%', 'GST18', 18], ['GST 28%', 'GST28', 28]] as [$name, $code, $rate]) {
            Tax::firstOrCreate(['code' => $code], ['name' => $name, 'rate' => $rate]);
        }

        foreach ([
            ['Piece', 'pcs', false], ['Set', 'set', false], ['Box', 'box', false], ['Pair', 'pair', false],
            ['Meter', 'm', true], ['Square foot', 'sqft', true], ['Kilogram', 'kg', true], ['Litre', 'ltr', true],
            ['Roll', 'roll', false], ['Packet', 'pkt', false],
        ] as [$name, $short, $decimal]) {
            Unit::firstOrCreate(['short_name' => $short], ['name' => $name, 'allow_decimal' => $decimal]);
        }

        foreach (['Rent', 'Salaries', 'Electricity', 'Transport', 'Maintenance', 'Marketing', 'Office supplies', 'Miscellaneous'] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }

        $this->seedSuperAdmin($branch);
    }

    private function seedSuperAdmin(Branch $branch): void
    {
        $email = config('app.seed_admin.email');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = config('app.seed_admin.password');
        if (! $password) {
            if (! app()->environment('local', 'testing')) {
                throw new RuntimeException('Set ADMIN_PASSWORD in .env before seeding outside local development.');
            }
            $password = 'password';
        }

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $password,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole(User::SUPER_ADMIN_ROLE);
    }
}
