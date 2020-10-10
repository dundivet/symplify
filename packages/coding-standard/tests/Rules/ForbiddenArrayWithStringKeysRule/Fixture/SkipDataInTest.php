<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\Tests\Rules\ForbiddenArrayWithStringKeysRule\Fixture;

final class SkipDataInTest
{
    public function run()
    {
        return [
            'key' => 'value'
        ];
    }
}
