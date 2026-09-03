<?php

namespace App\Http\Controllers;

/**
 * Abstract base controller.
 *
 * Intentionally empty: cross-cutting concerns (auth, tenancy, ops logging)
 * live in dedicated support classes per the module architecture in
 * sewdocs/03-technical-specs. Extend only for PSR-4 grouping.
 */
abstract class Controller
{
    //
}
