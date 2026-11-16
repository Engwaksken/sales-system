<?php

namespace Tests\Feature;

use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_product_and_avatar_paths_link_to_media_route(): void
    {
        Storage::disk('public')->put('products/shoe.png', UploadedFile::fake()->image('shoe.png')->getContent());

        $url = PublicMedia::url('products/shoe.png');
        $this->assertStringContainsString('/media/products/shoe.png?v=', $url);

        $this->assertStringContainsString('/media/avatars/me.jpg', PublicMedia::url('/storage/avatars/me.jpg'));
        $this->assertStringContainsString('/media/avatars/me.jpg', PublicMedia::url('public/avatars/me.jpg'));
        $this->assertSame('https://cdn.example.com/a.png', PublicMedia::url('https://cdn.example.com/a.png'));
        $this->assertNull(PublicMedia::url(''));
        $this->assertNull(PublicMedia::url(null));
    }

    public function test_media_route_serves_images_without_sign_in(): void
    {
        Storage::disk('public')->put('avatars/me.png', UploadedFile::fake()->image('me.png')->getContent());

        $this->get('/media/avatars/me.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_media_route_refuses_missing_files_other_folders_and_non_images(): void
    {
        Storage::disk('public')->put('chat-attachments/secret.png', UploadedFile::fake()->image('s.png')->getContent());
        Storage::disk('public')->put('products/notes.txt', 'hello');

        $this->get('/media/products/missing.png')->assertNotFound();
        $this->get('/media/chat-attachments/secret.png')->assertNotFound();
        $this->get('/media/products/notes.txt')->assertNotFound();
        $this->get('/media/products/..%2F.env')->assertNotFound();
    }
}
