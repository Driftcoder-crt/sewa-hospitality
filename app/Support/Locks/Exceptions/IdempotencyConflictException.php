<?php

namespace App\Support\Locks\Exceptions;

use RuntimeException;

/**
 * An `Idempotency-Key` was replayed with a DIFFERENT request fingerprint —
 * either a client bug or a replay attempt. The M3 API controllers map this
 * to HTTP 409 Conflict (04-api-spec.md §1 error table).
 */
class IdempotencyConflictException extends RuntimeException {}
