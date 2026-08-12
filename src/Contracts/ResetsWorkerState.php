<?php namespace Winter\Storm\Contracts;

/**
 * Implemented by services that retain state derived from a single operation.
 *
 * Under PHP-FPM every request gets a fresh process, so request-scoped state on a long-lived
 * object is harmless. Under a persistent application server such as Laravel Octane the same
 * object serves many requests, and anything derived from one request stays visible to the next.
 *
 * Winter invokes this at the start of every operation, after clearing core state and before the
 * request is dispatched. Plugins that cache per-request data on a plugin object, a singleton or a
 * static property should implement it.
 *
 * Two rules keep implementations safe:
 *
 * - **Be idempotent.** It may be called more than once for a single operation, including after an
 *   operation that threw partway through.
 * - **Do not unregister boot-time extensions.** Registration callbacks, aliases, navigation
 *   definitions and event listeners are built once per worker; discarding them leaves the worker
 *   permanently degraded. Clear only what a request produced.
 *
 * @author Winter CMS
 */
interface ResetsWorkerState
{
    /**
     * Discard state derived from the previous operation.
     */
    public function resetWorkerState(): void;
}
