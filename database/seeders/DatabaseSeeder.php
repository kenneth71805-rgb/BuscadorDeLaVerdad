<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/** Safe for production: seeds categories only. No users, no passwords. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'News', 'Articles and Features', 'Editorial and Opinion', 'Photojournalism and Galleries',
            'Sports', 'Literary and Creative Works', 'Events and Campus Activities', 'Special Issues and Publications',
        ];

        foreach ($categories as $i => $name) {
            Category::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name, 'sort_order' => $i]);
        }
    }
}
