<?php

namespace App\Modules\Blog\Services;

use App\Models\User;
use App\Modules\Ai\Services\TranslationDispatcher;
use App\Modules\Blog\Enums\PostStatus;
use App\Modules\Blog\Models\Post;
use App\Support\Audit\ActivityLogger;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Publish gate for posts (07-blog-news §5): the "admin author" defect
 * is STRUCTURALLY impossible — author_user_id is NOT NULL, the editor
 * requires a human author pick, and this gate re-verifies every publish
 * invariant. Category, cover alt, excerpt, metas — all enforced.
 */
final class PostPublishGate
{
    /**
     * Validate a post for publish (or schedule). Returns field-level
     * messages ([] = pass); throws on structural violations.
     *
     * @return array<string, string>
     */
    public function validate(Post $post): array
    {
        $errors = [];

        if ($post->author_user_id === null) {
            throw new InvalidArgumentException('A post cannot publish without a human author (authorship rule).');
        }

        if ($post->author?->hasRole('super-admin') && ! $post->author->hasAnyRole(['author', 'editor', 'admin'])) {
            // super-admin may publish, but a bare "admin" account cannot —
            // the reference's byline defect, enforced here.
            $errors['author_user_id'] = 'The author must be a named person with an author profile.';
        }

        if ($post->categories()->count() === 0) {
            $errors['categories'] = 'Pick at least one category.';
        }

        if (! $post->cover_media_id) {
            $errors['cover_media_id'] = 'A cover image is required to publish.';
        } elseif ($post->cover && ! $post->cover->hasUsableAltText()) {
            $errors['cover_media_id'] = 'The cover image needs alt text (media discipline).';
        }

        if (trim((string) $post->excerpt) === '' || mb_strlen((string) $post->excerpt) < 40) {
            $errors['excerpt'] = 'Write a real excerpt (at least 40 characters).';
        }

        foreach (['meta_title' => 60, 'meta_description' => 160] as $field => $max) {
            $value = trim((string) $post->{$field});

            if ($value === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)).' is required to publish.';
            } elseif (mb_strlen($value) > $max) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field))." should be ≤ {$max} characters.";
            }
        }

        if ($post->status === PostStatus::Scheduled && ! $post->scheduled_at) {
            $errors['scheduled_at'] = 'A scheduled post needs a publish time.';
        }

        return $errors;
    }

    /**
     * Review workflow: submit draft → review (author), approve →
     * publishable (editor+). Permissions are role-gated by callers;
     * this records the trail.
     */
    public function submitForReview(Post $post, ?User $actor = null): void
    {
        if ($post->status !== PostStatus::Draft) {
            throw new InvalidArgumentException('Only drafts can enter review.');
        }

        $post->status = PostStatus::Review;
        $post->save();

        ActivityLogger::log('admin', 'update', $post, ['status' => 'review', 'by' => $actor?->id]);
    }

    public function approve(Post $post, User $approver): void
    {
        if ($post->status !== PostStatus::Review) {
            throw new InvalidArgumentException('Only posts in review can be approved.');
        }

        if ($post->author_user_id === $approver->getKey()) {
            throw new InvalidArgumentException('Authors cannot approve their own posts (four-eyes rule).');
        }

        $post->approved_by_user_id = $approver->getKey();
        $post->save();

        ActivityLogger::log('admin', 'update', $post, ['status' => 'approved', 'approver' => $approver->id]);
    }

    /** Publish-or-schedule: gate-checked; scheduled rows fire via cron. */
    public function publish(Post $post, ?string $when = null): void
    {
        $errors = $this->validate($post);

        if ($errors !== []) {
            throw new InvalidArgumentException('Publish gate failed: '.implode(' ', $errors));
        }

        $post->computeCopyMetrics();

        if ($when !== null && $when !== '') {
            $post->status = PostStatus::Scheduled;
            $post->scheduled_at = Carbon::parse($when);
        } else {
            $post->status = PostStatus::Published;
            $post->published_at = $post->published_at ?? now();
            $post->scheduled_at = null;

            // Translation fan-out (11-multilingual §4): published EN post
            // enqueues machine-draft jobs per enabled locale (queue `ai`).
            TranslationDispatcher::forEntity($post);
        }

        $post->save();
    }
}
