<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Pricezaim\Sdk\Page;

final class PageTest extends TestCase
{
    public function test_page_falls_back_to_top_level_pagination_fields(): void
    {
        $page = Page::fromArray([
            'data' => [['id' => 1]],
            'total' => 3,
            'current_page' => 2,
            'last_page' => 3,
            'per_page' => 1,
        ]);

        $this->assertSame(3, $page->total());
        $this->assertSame(2, $page->currentPage());
        $this->assertSame(3, $page->lastPage());
        $this->assertSame(1, $page->perPage());
        $this->assertCount(1, $page);
    }
}
