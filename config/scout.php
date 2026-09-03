<?php

/*
|--------------------------------------------------------------------------
| Laravel Scout configuration
|--------------------------------------------------------------------------
| SCOUT_DRIVER=database is a locked decision (03-technical-specs/08-search.md):
| MySQL FULLTEXT indexes over posts, cities, housing_units, services and
| job_postings — no external search service on shared hosting. Typesense/
| Meilisearch remain a Phase-2 trigger, behind this same env flag.
*/

return [

    'driver' => env('SCOUT_DRIVER', 'database'),

    'soft_delete' => false,

    /*
     | Indexing is queued. SCOUT_QUEUE=false forces synchronous indexing
     | (used by tests). The queue name is fixed to `syncs`
     | (07-queues-scheduling.md §2–3: retried 3×, daily catch-up safe);
     | connection null → default database queue connection.
     */
    'queue' => env('SCOUT_QUEUE', true) ? [
        'connection' => null,
        'queue' => 'syncs',
    ] : false,

    // Explicit registry of searchable models (added per-module as milestones
    // land); empty means Scout only touches models implementing Searchable.
    'searchables' => [],

    'database' => [
        'mode' => env('SCOUT_DATABASE_MODE', 'AUTO'),
    ],

];
