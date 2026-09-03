<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Enums\PageStatus;
use App\Modules\Cms\Enums\PageType;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\PageRevision;
use App\Support\Cms\TextDiff;
use Throwable;

/**
 * Revision manager (04-modules/01-cms.md §4.7 + §5): every save →
 * revision row; last 20 kept; restore writes a NEW revision (the trail
 * is never destructive); diffing exposes structural block changes and
 * word-level text changes.
 */
class RevisionManager
{
    public const CAP = 20;

    /** Content fields captured in every revision snapshot. */
    private const SNAPSHOT_FIELDS = [
        'title', 'slug', 'type', 'template', 'meta_title',
        'meta_description', 'canonical_override', 'noindex',
        'noindex_reason', 'blocks', 'status',
    ];

    /** Record a revision if content actually changed. */
    public function record(Page $page, ?int $authorId = null): ?PageRevision
    {
        $current = $this->snapshot($page);
        $latest = $page->revisions()->first();

        if ($latest !== null && $this->sameSnapshot($latest->snapshot, $current)) {
            return null; // no content change → no revision noise
        }

        $revision = PageRevision::query()->create([
            'page_id' => $page->getKey(),
            'snapshot' => $current,
            'author_user_id' => $authorId,
        ]);

        $this->prune($page);

        return $revision;
    }

    /**
     * Restore: copy the snapshot onto the page, save, and record the
     * restore itself as a NEW revision — the trail never rewrites or
     * drops history (04-modules/01-cms.md §5).
     */
    public function restore(PageRevision $revision, ?int $authorId = null): Page
    {
        $page = $revision->page;
        $snapshot = $revision->snapshot ?? [];

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (array_key_exists($field, $snapshot)) {
                $page->{$field} = $snapshot[$field];
            }
        }

        // Belt and braces for legacy snapshots captured before defaults
        // were normalized: never write null into NOT NULL columns.
        $page->type ??= PageType::Standard;
        $page->template ??= 'default';
        $page->status ??= PageStatus::Draft;
        $page->noindex = (bool) ($page->noindex ?? false);

        $page->updated_by = $authorId;
        $page->save();

        // Revision recording is an explicit editor-side action (no model
        // observer exists), so the restore itself must append its own
        // trail entry — it is never a silent no-op.
        $this->record($page->refresh(), $authorId);

        return $page->refresh();
    }

    /**
     * Structural + text diff between two snapshots, rendered-ready:
     * per-block list with added/removed/changed markers and word-level
     * diffs for changed text fields.
     *
     * @return array{removed: list<string>, added: list<string>, changes: list<array{label: string, fields: list<array{field: string, ops: list<array{op: string, text: string}>}>}>}
     */
    public function diff(array $old, array $new): array
    {
        $oldBlocks = $this->blocksOf($old);
        $newBlocks = $this->blocksOf($new);

        $diff = ['removed' => [], 'added' => [], 'changes' => []];

        $count = max(count($oldBlocks), count($newBlocks));
        for ($i = 0; $i < $count; $i++) {
            $oldBlock = $oldBlocks[$i] ?? null;
            $newBlock = $newBlocks[$i] ?? null;
            $label = 'Block '.($i + 1);

            if ($oldBlock !== null) {
                $label = BlockRegistry::has($oldBlock['type'])
                    ? BlockRegistry::label($oldBlock['type'])
                    : ucfirst($oldBlock['type']);
            }

            if ($oldBlock !== null && $newBlock === null) {
                $diff['removed'][] = $label;

                continue;
            }

            if ($oldBlock === null && $newBlock !== null) {
                $diff['added'][] = BlockRegistry::has($newBlock['type'])
                    ? BlockRegistry::label($newBlock['type'])
                    : ucfirst($newBlock['type']);

                continue;
            }

            if ($oldBlock === null || $newBlock === null) {
                continue;
            }

            $fields = $this->blockDataDiff($oldBlock, $newBlock);
            if ($fields !== []) {
                $diff['changes'][] = ['label' => $label, 'fields' => $fields];
            }
        }

        // Page-level fields outside blocks.
        $metaFields = ['title', 'slug', 'meta_title', 'meta_description', 'noindex'];
        $pageChanges = [];
        foreach ($metaFields as $field) {
            $oldValue = (string) ($old[$field] ?? '');
            $newValue = (string) ($new[$field] ?? '');
            if ($oldValue !== $newValue) {
                $pageChanges[] = [
                    'field' => $field,
                    'ops' => TextDiff::words($oldValue, $newValue),
                ];
            }
        }
        if ($pageChanges !== []) {
            array_unshift($diff['changes'], ['label' => 'Page settings', 'fields' => $pageChanges]);
        }

        return $diff;
    }

    /** @return list<array{field: string, ops: list<array{op: string, text: string}>}> */
    private function blockDataDiff(array $oldBlock, array $newBlock): array
    {
        $oldData = is_array($oldBlock['data'] ?? null) ? $oldBlock['data'] : [];
        $newData = is_array($newBlock['data'] ?? null) ? $newBlock['data'] : [];
        $changes = [];

        $keys = array_unique([...array_keys($oldData), ...array_keys($newData)]);
        foreach ($keys as $key) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;

            if (is_string($oldValue) && is_string($newValue) && $oldValue !== $newValue) {
                $changes[] = ['field' => $key, 'ops' => TextDiff::words($oldValue, $newValue)];

                continue;
            }

            // Repeatable structures: summarize count + item titles.
            if (is_array($oldValue) && is_array($newValue) && $oldValue !== $newValue) {
                $oldCount = count($oldValue);
                $newCount = count($newValue);
                $summary = $oldCount === $newCount ? 'items edited' : "{$oldCount} → {$newCount} items";
                $changes[] = ['field' => $key, 'ops' => [
                    ['op' => 'same', 'text' => $summary],
                ]];
            } elseif ($oldValue !== $newValue) {
                $changes[] = ['field' => $key, 'ops' => [
                    ['op' => 'same', 'text' => 'value changed'],
                ]];
            }
        }

        return $changes;
    }

    private function snapshot(Page $page): array
    {
        $snap = [];
        foreach (self::SNAPSHOT_FIELDS as $field) {
            $value = $page->{$field};
            $snap[$field] = $value instanceof PageStatus ? $value->value : $value;
        }

        // Freshly created models don't know DB column defaults until they
        // are re-fetched — a snapshot taken before that carries nulls which
        // restore() would write into NOT NULL columns (type/template/status),
        // and which would make the next honest save look like a change
        // (noindex: null vs hydrated false).
        $snap['type'] = $snap['type'] ?: PageType::Standard->value;
        $snap['template'] = $snap['template'] ?: 'default';
        $snap['status'] = $snap['status'] ?: PageStatus::Draft->value;
        $snap['noindex'] = (bool) ($snap['noindex'] ?? false);

        return $snap;
    }

    private function sameSnapshot(mixed $a, array $b): bool
    {
        try {
            return json_encode($a) === (json_encode($b));
        } catch (Throwable) {
            return false;
        }
    }

    private function prune(Page $page): void
    {
        $keep = PageRevision::query()
            ->where('page_id', $page->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::CAP)
            ->pluck('id');

        PageRevision::query()
            ->where('page_id', $page->getKey())
            ->whereNotIn('id', $keep)
            ->delete();
    }

    /** @return list<array{type: string, data: mixed}> */
    private function blocksOf(array $snapshot): array
    {
        $blocks = $snapshot['blocks'] ?? [];

        return is_array($blocks) ? array_values($blocks) : [];
    }
}
