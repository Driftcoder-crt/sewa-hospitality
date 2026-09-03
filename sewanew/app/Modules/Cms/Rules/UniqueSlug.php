<?php

declare(strict_types=1);

namespace Modules\Cms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Cms\Models\Page;

/**
 * Unique Slug Rule
 */
class UniqueSlug implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?string $ignoreId = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Page::where('slug', $value);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail("The {$attribute} has already been taken.");
        }
    }
}
