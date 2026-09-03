<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Enums\PostType;
use App\Modules\Blog\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Post factory (editorial tests). */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5);

        return [
            'slug' => Str::slug($title.'-'.Str::random(4)),
            'type' => PostType::Blog,
            'title' => $title,
            'excerpt' => 'A real excerpt long enough for the publish gate to accept it without complaints.',
            'body' => '<h2>Overview</h2><p>'.str_repeat('Honest, dated, practical copy. ', 40).'</p>',
            'status' => PostStatus::Draft,
            'author_user_id' => User::factory(),
            'locale' => 'en',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(3),
        ]);
    }
}
