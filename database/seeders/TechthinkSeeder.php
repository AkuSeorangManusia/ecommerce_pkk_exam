<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TechthinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Brands
        $brands = [
            ['name' => 'NVIDIA', 'description' => 'Leading GPU manufacturer', 'website' => 'https://nvidia.com'],
            ['name' => 'AMD', 'description' => 'CPU and GPU manufacturer', 'website' => 'https://amd.com'],
            ['name' => 'Intel', 'description' => 'Leading CPU manufacturer', 'website' => 'https://intel.com'],
            ['name' => 'ASUS', 'description' => 'Computer hardware manufacturer', 'website' => 'https://asus.com'],
            ['name' => 'MSI', 'description' => 'Gaming hardware manufacturer', 'website' => 'https://msi.com'],
            ['name' => 'Logitech', 'description' => 'Peripherals manufacturer', 'website' => 'https://logitech.com'],
            ['name' => 'Razer', 'description' => 'Gaming peripherals brand', 'website' => 'https://razer.com'],
            ['name' => 'Corsair', 'description' => 'PC components and peripherals', 'website' => 'https://corsair.com'],
            ['name' => 'Samsung', 'description' => 'Electronics manufacturer', 'website' => 'https://samsung.com'],
            ['name' => 'Kingston', 'description' => 'Memory and storage solutions', 'website' => 'https://kingston.com'],
        ];

        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']),
                'description' => $brand['description'],
                'website' => $brand['website'],
                'is_active' => true,
            ]);
        }

        // Create Parent Categories
        $parentCategories = [
            ['name' => 'Components', 'description' => 'PC components and internal parts'],
            ['name' => 'Peripherals', 'description' => 'External devices and accessories'],
            ['name' => 'Storage', 'description' => 'Storage devices and solutions'],
            ['name' => 'Displays', 'description' => 'Monitors and display solutions'],
        ];

        foreach ($parentCategories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }

        // Create Child Categories
        $childCategories = [
            // Components children
            ['name' => 'CPUs / Processors', 'parent' => 'Components'],
            ['name' => 'Graphics Cards', 'parent' => 'Components'],
            ['name' => 'Motherboards', 'parent' => 'Components'],
            ['name' => 'RAM / Memory', 'parent' => 'Components'],
            ['name' => 'Power Supplies', 'parent' => 'Components'],
            ['name' => 'PC Cases', 'parent' => 'Components'],
            ['name' => 'Cooling', 'parent' => 'Components'],
            // Peripherals children
            ['name' => 'Keyboards', 'parent' => 'Peripherals'],
            ['name' => 'Mice', 'parent' => 'Peripherals'],
            ['name' => 'Headsets', 'parent' => 'Peripherals'],
            ['name' => 'Webcams', 'parent' => 'Peripherals'],
            ['name' => 'Speakers', 'parent' => 'Peripherals'],
            // Storage children
            ['name' => 'SSDs', 'parent' => 'Storage'],
            ['name' => 'HDDs', 'parent' => 'Storage'],
            ['name' => 'External Drives', 'parent' => 'Storage'],
            // Displays children
            ['name' => 'Gaming Monitors', 'parent' => 'Displays'],
            ['name' => 'Office Monitors', 'parent' => 'Displays'],
            ['name' => 'Ultrawide Monitors', 'parent' => 'Displays'],
        ];

        foreach ($childCategories as $category) {
            $parent = Category::where('name', $category['parent'])->first();
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'parent_id' => $parent->id,
                'is_active' => true,
            ]);
        }

        // Create sample products
        $products = [
            [
                'name' => 'NVIDIA GeForce RTX 4090',
                'category' => 'Graphics Cards',
                'brand' => 'NVIDIA',
                'price' => 35000000,
                'compare_price' => 38000000,
                'stock' => 5,
                'short_description' => 'The ultimate gaming GPU with 24GB GDDR6X memory',
                'specifications' => [
                    'VRAM' => '24GB GDDR6X',
                    'CUDA Cores' => '16384',
                    'Boost Clock' => '2.52 GHz',
                    'TDP' => '450W',
                    'Memory Bus' => '384-bit',
                ],
            ],
            [
                'name' => 'AMD Ryzen 9 7950X',
                'category' => 'CPUs / Processors',
                'brand' => 'AMD',
                'price' => 11500000,
                'compare_price' => 12500000,
                'stock' => 12,
                'short_description' => '16-core, 32-thread desktop processor',
                'specifications' => [
                    'Cores' => '16',
                    'Threads' => '32',
                    'Base Clock' => '4.5 GHz',
                    'Boost Clock' => '5.7 GHz',
                    'TDP' => '170W',
                    'Socket' => 'AM5',
                ],
            ],
            [
                'name' => 'Intel Core i9-14900K',
                'category' => 'CPUs / Processors',
                'brand' => 'Intel',
                'price' => 12000000,
                'stock' => 8,
                'short_description' => '24-core (8P+16E) desktop processor',
                'specifications' => [
                    'P-Cores' => '8',
                    'E-Cores' => '16',
                    'Threads' => '32',
                    'Boost Clock' => '6.0 GHz',
                    'TDP' => '125W',
                    'Socket' => 'LGA1700',
                ],
            ],
            [
                'name' => 'ASUS ROG Strix B650E-F Gaming',
                'category' => 'Motherboards',
                'brand' => 'ASUS',
                'price' => 6500000,
                'stock' => 15,
                'short_description' => 'AM5 motherboard with PCIe 5.0 and DDR5 support',
                'specifications' => [
                    'Socket' => 'AM5',
                    'Chipset' => 'B650E',
                    'Memory' => 'DDR5 up to 6400MHz',
                    'PCIe' => '5.0 x16',
                    'M.2 Slots' => '4',
                ],
            ],
            [
                'name' => 'Corsair Vengeance DDR5 32GB',
                'category' => 'RAM / Memory',
                'brand' => 'Corsair',
                'price' => 2800000,
                'stock' => 25,
                'short_description' => '32GB (2x16GB) DDR5-6000 RAM kit',
                'specifications' => [
                    'Capacity' => '32GB (2x16GB)',
                    'Speed' => '6000MHz',
                    'Latency' => 'CL36',
                    'Voltage' => '1.35V',
                ],
            ],
            [
                'name' => 'Samsung 990 Pro 2TB',
                'category' => 'SSDs',
                'brand' => 'Samsung',
                'price' => 4200000,
                'stock' => 20,
                'short_description' => 'PCIe 4.0 NVMe M.2 SSD with heatsink',
                'specifications' => [
                    'Capacity' => '2TB',
                    'Interface' => 'PCIe 4.0 x4, NVMe 2.0',
                    'Read Speed' => '7450 MB/s',
                    'Write Speed' => '6900 MB/s',
                    'Form Factor' => 'M.2 2280',
                ],
            ],
            [
                'name' => 'Logitech G Pro X Superlight 2',
                'category' => 'Mice',
                'brand' => 'Logitech',
                'price' => 2500000,
                'stock' => 30,
                'short_description' => 'Ultra-lightweight wireless gaming mouse',
                'specifications' => [
                    'Weight' => '60g',
                    'Sensor' => 'HERO 2',
                    'DPI' => '32000',
                    'Battery Life' => '95 hours',
                    'Polling Rate' => '4000Hz',
                ],
            ],
            [
                'name' => 'Razer BlackWidow V4 Pro',
                'category' => 'Keyboards',
                'brand' => 'Razer',
                'price' => 4500000,
                'stock' => 18,
                'short_description' => 'Mechanical gaming keyboard with Razer Green switches',
                'specifications' => [
                    'Switch' => 'Razer Green',
                    'Layout' => 'Full-size',
                    'Backlighting' => 'Razer Chroma RGB',
                    'Wrist Rest' => 'Included',
                    'Media Keys' => 'Dedicated',
                ],
            ],
            [
                'name' => 'ASUS ROG Swift PG27AQN',
                'category' => 'Gaming Monitors',
                'brand' => 'ASUS',
                'price' => 16000000,
                'compare_price' => 18000000,
                'stock' => 6,
                'short_description' => '27" 1440p 360Hz gaming monitor',
                'specifications' => [
                    'Resolution' => '2560x1440',
                    'Refresh Rate' => '360Hz',
                    'Panel' => 'Fast IPS',
                    'Response Time' => '1ms',
                    'G-Sync' => 'Yes',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Corsair RM1000x',
                'category' => 'Power Supplies',
                'brand' => 'Corsair',
                'price' => 3200000,
                'stock' => 22,
                'short_description' => '1000W 80+ Gold fully modular PSU',
                'specifications' => [
                    'Wattage' => '1000W',
                    'Efficiency' => '80+ Gold',
                    'Modular' => 'Fully Modular',
                    'Fan Size' => '135mm',
                    'Warranty' => '10 years',
                ],
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::where('name', $productData['category'])->first();
            $brand = Brand::where('name', $productData['brand'])->first();

            Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'sku' => strtoupper(Str::random(8)),
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'price' => $productData['price'],
                'compare_price' => $productData['compare_price'] ?? null,
                'stock' => $productData['stock'],
                'short_description' => $productData['short_description'],
                'specifications' => $productData['specifications'],
                'is_featured' => $productData['is_featured'] ?? false,
                'is_active' => true,
            ]);
        }

        // Create sample customers
        $customers = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '081234567890', 'city' => 'Jakarta', 'address' => 'Jl. Sudirman No. 1'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '081234567891', 'city' => 'Bandung', 'address' => 'Jl. Braga No. 10'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'phone' => '081234567892', 'city' => 'Surabaya', 'address' => 'Jl. Tunjungan No. 5'],
        ];

        foreach ($customers as $customerData) {
            Customer::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'city' => $customerData['city'],
                'address' => $customerData['address'],
                'postal_code' => '12345',
                'country' => 'Indonesia',
                'is_active' => true,
            ]);
        }

        // Create a sample order
        $customer = Customer::first();
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'subtotal' => 47500000,
            'tax' => 4750000,
            'shipping_cost' => 50000,
            'discount' => 0,
            'total' => 52300000,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'shipping_name' => $customer->name,
            'shipping_phone' => $customer->phone,
            'shipping_address' => $customer->address,
            'shipping_city' => $customer->city,
            'shipping_postal_code' => '12345',
            'shipping_country' => 'Indonesia',
            'paid_at' => now(),
        ]);

        // Add order items
        $products = Product::take(2)->get();
        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => 1,
                'unit_price' => $product->price,
                'subtotal' => $product->price,
            ]);
        }

        $this->command->info('Techthink sample data seeded successfully!');
    }
}
