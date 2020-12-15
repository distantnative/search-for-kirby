<?php

namespace Kirby\Search;

final class IndexTest extends TestCase
{
    public function testInstance(): void
    {
        $index = $this->index();
        $this->assertTrue(is_a($index, 'Kirby\Search\Index'));

        $provider = $index->provider();
        $this->assertTrue(is_subclass_of($provider, 'Kirby\Search\Provider'));
        $this->assertTrue(is_a($provider, 'Kirby\Search\Providers\Mockup'));
    }
}
