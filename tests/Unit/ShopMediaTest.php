<?php

namespace Tests\Unit;

use App\Support\ShopMedia;
use Tests\TestCase;

class ShopMediaTest extends TestCase
{
    public function test_it_builds_storage_url_for_relative_paths(): void
    {
        $this->assertSame(asset('storage/gateway-icons/zp.png'), ShopMedia::url('gateway-icons/zp.png'));
        $this->assertNull(ShopMedia::url(null));
        $this->assertNull(ShopMedia::url(''));
        $this->assertSame('https://cdn.example.com/icon.png', ShopMedia::url('https://cdn.example.com/icon.png'));
    }
}
