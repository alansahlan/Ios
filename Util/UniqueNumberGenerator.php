<?php

namespace Ihsan\UsernameGenerator\Util;

/**
 * @author Muhammad Surya Ihsanuddin <surya.kejawen@gmail.com>
 */
class UniqueNumberGenerator
{
    /**
     * @return int
     */
    public static function generate()
    {
        return rand(1000, 9999);
    }
}
