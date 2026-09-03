<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Page Request
 */
class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('page'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $page = $this->route('page');
        $pageId = $page ? $page->id : null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($pageId)],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'template' => ['required', 'string', 'in:default,landing,minimal,full-width'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'seo_data' => ['nullable', 'array'],
            'schema_markup' => ['nullable', 'array'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'no_index' => ['boolean'],
            'no_follow' => ['boolean'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:255'],
            'twitter_card' => ['nullable', 'string', 'in:summary,summary_large_image,app,player'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'sort_order' => ['integer', 'min:0'],
            'blocks' => ['nullable', 'array'],
            'blocks.*.id' => ['nullable', 'exists:cms_blocks,id'],
            'blocks.*.type' => ['required_with:blocks', 'string'],
            'blocks.*.name' => ['required_with:blocks', 'string', 'max:255'],
            'blocks.*.data' => ['nullable', 'array'],
            'blocks.*.template' => ['nullable', 'string'],
            'blocks.*.order' => ['nullable', 'integer', 'min:0'],
            'blocks.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A page title is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already in use.',
            'meta_title.max' => 'Meta title should be less than 60 characters for optimal SEO.',
            'meta_description.max' => 'Meta description should be less than 160 characters for optimal SEO.',
            'blocks.*.id.exists' => 'One or more blocks are invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'og_image' => 'Open Graph image',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure blocks have proper structure
        if ($this->has('blocks')) {
            $blocks = collect($this->input('blocks'))->map(function ($block, $index) {
                return array_merge([
                    'order' => $index,
                    'is_active' => true,
                ], $block);
            })->toArray();

            $this->merge(['blocks' => $blocks]);
        }
    }
}
