<?php

namespace App\Console\Commands;

use App\Models\Media;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Safety-first orphan sweep (03-technical-specs/09-media-pipeline.md §7,
 * scheduled weekly Sun 03:00 in routes/console.php).
 *
 * Spatie's DefaultPathGenerator stores media as
 *   {model morph class, \ → /}/{model id}/{collection}/{files}
 * under the disk root. Morph classes span MULTIPLE path segments
 * (e.g. App/Models/User), so iterating only the top-level directories and
 * reading each name as a morph class would misfire. Instead this command
 * walks the tree and treats a directory as a "class root" when
 *   (a) the media table has rows whose model_type equals the path segments
 *       joined with '\', OR
 *   (b) shape fallback: every child directory is numeric — a dead class
 *       root whose rows are all gone (the classic "model rows deleted
 *       without media cleanup" case the manifest targets).
 *
 * Under a class root every child directory is a model-id directory. It is
 * referenced iff a media row exists with model_type = class and model_id =
 * the directory name (string binding survives integer ids under
 * sqlite/MySQL and ULID-ish ids under sqlite's dynamic typing). A class
 * root with zero rows therefore has EVERY child flagged — the equivalent
 * of the manifest's "count where model_type = class; if 0 → orphan" — but
 * the deletion decision is still made per model id, so nothing referenced
 * by a DB row can ever be deleted.
 *
 * Safety posture (error-locks doctrine):
 * - DRY-RUN by default ([dry-run] lines); --force performs the deletions.
 * - Local disks only; files, dot entries and symlinks are never touched.
 * - Deletions recurse only inside a confirmed-orphan model-id directory;
 *   emptied ancestors are pruned afterwards (never the disk root itself).
 * - Every run logs a structured summary to the `ops` channel.
 */
final class MediaPrune extends Command
{
    protected $signature = 'media:prune
                            {--force : Actually delete orphaned directories (default: dry-run)}
                            {--disk=public : Disk to sweep}';

    protected $description = 'Sweep orphaned media directories whose model rows are gone (dry-run by default) — 09-media-pipeline §7.';

    private int $classRoots = 0;

    private int $modelDirs = 0;

    private int $referenced = 0;

    private int $orphans = 0;

    private int $reclaimableBytes = 0;

    private int $skippedEntries = 0;

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $force = (bool) $this->option('force');

        if (config("filesystems.disks.{$diskName}.driver") !== 'local') {
            $this->error("media:prune only sweeps local disks — [{$diskName}] is not a local disk.");

            return self::FAILURE;
        }

        $disk = Storage::disk($diskName);
        $root = $disk->path('');

        if (! is_dir($root)) {
            $this->info("Disk [{$diskName}] root [{$root}] does not exist — nothing to sweep.");
            $this->logResult($diskName, $force);

            return self::SUCCESS;
        }

        try {
            $this->walk($root, '', $force);
        } catch (Throwable $e) {
            Log::channel('ops')->error('media.prune.failed', [
                'disk' => $diskName,
                'force' => $force,
                'error' => $e->getMessage(),
            ]);

            $this->error("media:prune aborted: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->renderSummary($diskName, $force);
        $this->logResult($diskName, $force);

        if ($this->orphans > 0 && ! $force) {
            $this->line('');
            $this->warn('Dry-run only — re-run with --force to delete the orphaned directories.');
        }

        return self::SUCCESS;
    }

    /**
     * Walk one directory of the disk. $relative is '' at the disk root and
     * '/'-separated below it (matching the Spatie path convention so the
     * morph class is a plain str_replace away).
     */
    private function walk(string $root, string $relative, bool $force): void
    {
        $absolute = $relative === ''
            ? $root
            : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        $entries = @scandir($absolute);

        if ($entries === false) {
            $this->skippedEntries++;

            return;
        }

        $childDirs = [];

        foreach ($entries as $entry) {
            // Dot entries are filesystem noise, never media content.
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $childAbsolute = $absolute.DIRECTORY_SEPARATOR.$entry;

            // Never touch non-directory entries (loose files, .gitignore,
            // stray uploads) — they are reported, not deleted.
            if (! is_dir($childAbsolute) || is_link($childAbsolute)) {
                $this->skippedEntries++;

                continue;
            }

            $childDirs[] = [
                'name' => $entry,
                'absolute' => $childAbsolute,
                'relative' => $relative === '' ? $entry : $relative.'/'.$entry,
            ];
        }

        if ($childDirs === []) {
            return;
        }

        // The disk root itself is never a morph class root.
        if ($relative === '') {
            foreach ($childDirs as $child) {
                $this->walk($root, $child['relative'], $force);
            }

            return;
        }

        $morphClass = str_replace('/', '\\', $relative);
        $rowExists = Media::query()->where('model_type', $morphClass)->exists();

        $allChildrenNumeric = ! in_array(
            false,
            array_map(static fn (array $child): bool => ctype_digit($child['name']), $childDirs),
            true,
        );

        if ($rowExists || $allChildrenNumeric) {
            $this->classRoots++;
            $this->sweepModelDirs($root, $morphClass, $childDirs, $force);

            return;
        }

        foreach ($childDirs as $child) {
            $this->walk($root, $child['relative'], $force);
        }
    }

    /**
     * @param  array<int, array{name: string, absolute: string, relative: string}>  $childDirs
     */
    private function sweepModelDirs(string $root, string $morphClass, array $childDirs, bool $force): void
    {
        foreach ($childDirs as $child) {
            $this->modelDirs++;

            // The model id is the directory name; a string binding matches
            // integer ids (affinity-aware) and text ids alike.
            $referenced = Media::query()
                ->where('model_type', $morphClass)
                ->where('model_id', $child['name'])
                ->exists();

            if ($referenced) {
                $this->referenced++;

                continue;
            }

            $this->orphans++;
            [$files, $bytes] = $this->measure($child['absolute']);
            $this->reclaimableBytes += $bytes;

            $label = sprintf(
                '%s (%d files, %s) — no media rows for %s#%s',
                $child['relative'],
                $files,
                $this->humanBytes($bytes),
                $morphClass,
                $child['name'],
            );

            if ($force) {
                $this->deleteDirectory($child['absolute']);
                $this->cleanupEmptyAncestors($root, dirname($child['absolute']));
                $this->line("deleted      {$label}");
            } else {
                $this->line("[dry-run]    would delete  {$label}");
            }
        }
    }

    /** Count files + bytes under an orphaned directory (recursive). */
    private function measure(string $absolute): array
    {
        $files = 0;
        $bytes = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $files++;
                $bytes += $item->getSize();
            }
        }

        return [$files, $bytes];
    }

    /** Recursively delete a confirmed-orphan directory. */
    private function deleteDirectory(string $absolute): void
    {
        $entries = @scandir($absolute);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $absolute.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($absolute);
    }

    /**
     * After removing an orphan, drop now-empty ancestor directories so the
     * sweep does not leave skeletons behind. Stops at the first non-empty
     * directory and NEVER climbs above the disk root.
     */
    private function cleanupEmptyAncestors(string $root, string $dir): void
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);

        while (str_starts_with($dir, $root) && $dir !== $root) {
            $entries = @scandir($dir);

            if ($entries === false || count($entries) > 2) {
                return; // non-empty (beyond . and ..) — stop
            }

            if (! @rmdir($dir)) {
                return;
            }

            $dir = dirname($dir);
        }
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return sprintf('%.1f MB', $bytes / (1024 * 1024));
        }

        if ($bytes >= 1024) {
            return sprintf('%.1f KB', $bytes / 1024);
        }

        return "{$bytes} B";
    }

    private function renderSummary(string $diskName, bool $force): void
    {
        $mode = $force ? 'PRUNE (--force)' : 'DRY-RUN';

        $this->info("media:prune [{$mode}] on disk [{$diskName}] — summary");
        $this->line("  class roots inspected : {$this->classRoots}");
        $this->line("  model dirs inspected  : {$this->modelDirs}");
        $this->line("  referenced (kept)     : {$this->referenced}");
        $this->line("  orphans found         : {$this->orphans}");
        $this->line('  bytes '.($force ? 'freed' : 'reclaimable')."    : {$this->reclaimableBytes} ({$this->humanBytes($this->reclaimableBytes)})");
        $this->line("  skipped entries       : {$this->skippedEntries}");
    }

    private function logResult(string $diskName, bool $force): void
    {
        Log::channel('ops')->info('media.prune.completed', [
            'disk' => $diskName,
            'force' => $force,
            'class_roots' => $this->classRoots,
            'model_dirs' => $this->modelDirs,
            'referenced' => $this->referenced,
            'orphans' => $this->orphans,
            'bytes' => $this->reclaimableBytes,
            'skipped_entries' => $this->skippedEntries,
        ]);
    }
}
