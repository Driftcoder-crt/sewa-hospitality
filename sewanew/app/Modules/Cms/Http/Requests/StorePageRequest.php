<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Page Request
 */
class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Cms\Models\Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:cms_pages,slug'],
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
            'blocks.*.type' => ['required_with:blocks', 'string'],
            'blocks.*.name' => ['required_with:blocks', 'string', 'max:255'],
            'blocks.*.data' => ['nullable', 'array'],
            'blocks.*.template' => ['nullable', 'string'],
            'blocks.*.order' => ['nullable', 'integer', 'min:0'],
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
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if slug is unique when creating
            if ($this->slug && $this->route('page') === null) {
                if (\Modules\Cms\Models\Page::where('slug', $this->slug)->exists()) {
                    $validator->errors()->add('slug', 'This slug is already in use.');
                }
            }
        });
    }
}
