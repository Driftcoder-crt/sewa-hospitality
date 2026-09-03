<?php

namespace App\Modules\Cities\Events;

use App\Modules\Cities\Models\City;

/** CityPublished (event catalog): sitemap + cache + search follow. */
class CityPublished
{
    public function __construct(public readonly City $city) {}
}
