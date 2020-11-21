<?php

namespace Kirby\Search;

final class ResultsTest extends TestCase
{
    public function testEmpty(): void
    {
        $results = new Results([]);

        $this->assertSame(0, $results->totalCount());
        $this->assertSame(0, $results->pagination()->total());
        // $this->assertSame(1, $results->pagination()->page());
        $this->assertSame(20, $results->pagination()->limit());
    }
}
