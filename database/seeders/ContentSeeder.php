<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Content::truncate();

        Content::create([
            'content' => '<p>Reference site about Lorem Ipsum, giving information on its origins, as well as a random Lipsum generator.</p>',
            'type'    => 'pp'
        ]);

        Content::create([
            'content' => '<p>Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries for previewing layouts and visual mockups.</p>',
            'type'    => 'tc'
        ]);
    }
}
