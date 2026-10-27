<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample multi-category catalogue for local development. Never runs in production.
 */
class DemoDataSeeder extends Seeder
{
    public function run(ProductService $products): void
    {
        if (app()->isProduction() || Product::exists()) {
            return;
        }

        $admin = User::role(User::SUPER_ADMIN_ROLE)->firstOrFail();
        $branch = Branch::where('code', 'MAIN')->firstOrFail();
        auth()->setUser($admin);

        $categories = collect(['Furniture', 'Electrical Switches', 'Electrical Accessories', 'Electronics', 'Hardware', 'Home Appliances'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])]);

        $brands = collect(['Godrej', 'Anchor', 'Havells', 'Philips', 'Bosch', 'Generic'])
            ->mapWithKeys(fn ($name) => [$name => Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])]);

        $pcs = Unit::where('short_name', 'pcs')->first();
        $m = Unit::where('short_name', 'm')->first();
        $gst18 = Tax::where('code', 'GST18')->first();
        $gst12 = Tax::where('code', 'GST12')->first();

        $catalogue = [
            ['Furniture', 'Godrej', $pcs, $gst18, 'Office chair ergonomic', 'FUR-CHR-001', 4500, 6999, ['material' => 'Mesh', 'colour' => 'Black']],
            ['Furniture', 'Godrej', $pcs, $gst18, 'Teak dining table 6 seater', 'FUR-TBL-001', 18000, 26500, ['material' => 'Teak', 'dimensions' => '180x90x75 cm']],
            ['Electrical Switches', 'Anchor', $pcs, $gst18, 'Modular switch 6A', 'ELS-SW-6A', 38, 65, ['rating' => '6A', 'type' => 'One-way']],
            ['Electrical Switches', 'Anchor', $pcs, $gst18, 'Modular socket 16A', 'ELS-SK-16A', 120, 185, ['rating' => '16A']],
            ['Electrical Accessories', 'Havells', $m, $gst18, 'FR copper wire 1.5 sqmm', 'ELA-WIR-15', 18, 27, ['gauge' => '1.5 sqmm', 'colour' => 'Red']],
            ['Electrical Accessories', 'Philips', $pcs, $gst12, 'LED bulb 9W', 'ELA-LED-9W', 55, 99, ['wattage' => '9W', 'base' => 'B22']],
            ['Electronics', 'Philips', $pcs, $gst18, 'Bluetooth speaker', 'ELC-SPK-001', 1200, 1899, ['battery' => '10 h']],
            ['Hardware', 'Generic', $pcs, $gst18, 'Stainless hinge 4 inch', 'HW-HNG-4IN', 35, 60, ['size' => '4 in', 'material' => 'SS304']],
            ['Home Appliances', 'Bosch', $pcs, $gst18, 'Mixer grinder 750W', 'HA-MIX-750', 3200, 4499, ['wattage' => '750W', 'jars' => 3]],
            ['Home Appliances', 'Havells', $pcs, $gst18, 'Ceiling fan 1200mm', 'HA-FAN-1200', 1800, 2650, ['sweep' => '1200 mm', 'wattage' => '75W']],
        ];

        foreach ($catalogue as [$category, $brand, $unit, $tax, $name, $sku, $cost, $price, $specs]) {
            $products->create([
                'category_id' => $categories[$category]->id,
                'brand_id' => $brands[$brand]->id,
                'unit_id' => $unit->id,
                'tax_id' => $tax->id,
                'name' => $name,
                'sku' => $sku,
                'cost_price' => $cost,
                'selling_price' => $price,
                'min_stock' => 5,
                'specifications' => $specs,
                'status' => 'active',
                'opening_stock' => 50,
                'opening_branch_id' => $branch->id,
            ], $admin);
        }

        Customer::factory()->count(10)->create();
        Supplier::factory()->count(5)->create();
    }
}
