# Agent guide — Winter Storm

Before writing **any** filesystem, path, string, or HTTP helper, search Storm — then Laravel, then Symfony — for an existing one. Storm sits on top of Laravel which sits on top of Symfony components; the helper you want almost certainly already exists at one of those layers. Hand-rolling a function that already exists creates duplicate logic with subtly different edge-case behaviour — the security-relevant bugs (path-traversal, prefix collisions, symlink escape, open_basedir respect) almost always live in *your* version, not the canonical one.

## Where to look (in this order)

1. **Storm itself.** Each `src/<Module>/` directory has a `README.md` that catalogues its public API. Read those before writing anything that "feels generic":
   - `src/Filesystem/` — paths, file IO, recursive directory ops, drive-letter handling, open_basedir-safe resolution
     - `PathResolver` (static class): `resolve()` (realpath that works on non-existent paths and respects open_basedir), `within()` (safe directory-containment check, separator-boundary aware after the GHSA-58fp fix), `join()`, `standardize()`
     - `Filesystem` (extends Illuminate's): `isAbsolutePath()`, `isLocalPath()`, `existsInsensitive()`, `symbolizePath()`, `chmodRecursive()`, plus everything Illuminate's gives you (`deleteDirectory()`, `cleanDirectory()`, `copyDirectory()`, etc.)
   - `src/Support/` — strings, arrays, class loading, helpers
   - `src/Network/` — HTTP requests, headers
   - `src/Html/` — HTML/URL building, sanitising
   - `src/Parse/` — YAML, INI, env, syntax/template parsing, Assetic filter wrappers
   - `src/Database/`, `src/Halcyon/`, `src/Auth/`, `src/Config/`, `src/Translation/`, `src/Mail/`, `src/Argon/`, `src/Flash/`, `src/Validation/`

2. **Laravel (Illuminate).** Storm depends on Laravel, so everything Laravel ships is available:
   - `Illuminate\Support\Str` — `Str::startsWith/endsWith/contains/before/after/between/slug/camel/snake/kebab/studly/random/uuid/limit/mask/finish/start/of/headline/title`. Use these instead of writing regex one-liners.
   - `Illuminate\Support\Arr` — `Arr::get/set/has/forget/only/except/dot/undot/flatten/pluck/wrap/divide/random/last/first/sort/sortRecursive/where/whereNotNull/query`. Use these instead of nested foreach loops.
   - `Illuminate\Support\Collection` (via `collect()`) — chainable map/filter/reduce/groupBy/pipe/tap. Often clearer than imperative array manipulation.
   - `Illuminate\Filesystem\Filesystem` (Storm's parent class) — `deleteDirectory()`, `cleanDirectory()`, `copyDirectory()`, `moveDirectory()`, `allFiles()`, `glob()`, `isDirectory()`, `prepend()`, `append()`, `replace()`, `hash()`.
   - Global helpers (always loaded): `data_get()`, `data_set()`, `data_fill()`, `value()`, `with()`, `tap()`, `optional()`, `transform()`, `head()`, `last()`, `class_basename()`, `class_uses_recursive()`, `now()`, `today()`, `e()`, `__()`/`trans()`, `dispatch()`, `event()`, `cache()`, `config()`, `env()`, `app()`, `resolve()`, `back()`, `redirect()`, `response()`, `view()`, `route()`, `url()`, `report()`, `rescue()`, `retry()`, `throw_if()`/`throw_unless()`, `abort()`/`abort_if()`/`abort_unless()`.
   - Facades for everything: `Cache`, `Config`, `DB`, `Event`, `File`, `Hash`, `Http`, `Lang`, `Log`, `Mail`, `Queue`, `Redis`, `Route`, `Schema`, `Session`, `Storage`, `URL`, `Validator`, `View`.

3. **Symfony components.** Many are installed (`vendor/symfony/`): `console`, `css-selector`, `error-handler`, `event-dispatcher`, `finder`, `http-foundation`, `http-kernel`, `mailer`, `mime`, `process`, `routing`, `string`, `translation`, `uid`, `var-dumper`, `yaml`. The ones agents reach for most often:
   - `Symfony\Component\Finder\Finder` — file finding (`Finder::create()->files()->name('*.less')->in($dir)`) — much cleaner than RecursiveIteratorIterator + RegexIterator chains.
   - `Symfony\Component\Filesystem\Filesystem` (different from Illuminate's — atomic operations) — `dumpFile()` (atomic write), `mirror()`, `mkdir()` (idempotent), `remove()`, `rename()`, `symlink()`, `appendToFile()`, `chmod()`/`chown()`/`chgrp()` with recursive flag.
   - `Symfony\Component\Process\Process` — run external commands safely (handles escaping, timeouts, streaming output) instead of `exec()` / `shell_exec()`.
   - `Symfony\Component\String\` — Unicode-aware string handling (`u()` / `b()` helpers, grapheme-cluster-safe operations).
   - `Symfony\Component\Yaml\Yaml` — strict YAML parse/dump.
   - `Symfony\Component\Uid\Uuid` / `Ulid` — modern UUID/ULID generation.

`grep -rl 'function <thing-you-need>' src/ vendor/laravel/framework/src/ vendor/symfony/` is a 10-second check that prevents 100-line mistakes.

## Concrete substitutions (worth memorising)

Paths and filesystem:

| If you reach for… | Use this instead |
|---|---|
| `realpath()` plus null-check plus trim slash | `PathResolver::resolve()` (handles non-existent paths and open_basedir) |
| `str_starts_with($path, $root)` to check containment | `PathResolver::within($path, $root)` — separator-boundary safe, defeats prefix-collision attacks |
| Manual `'/' . ltrim(...)` path join | `PathResolver::join()` |
| Custom recursive `rmrf()` in tests | `(new Winter\Storm\Filesystem\Filesystem())->deleteDirectory($path)` or `File::deleteDirectory($path)` |
| Detect absolute path | `(new Filesystem())->isAbsolutePath($path)` (covers Unix, Windows drive letter, URL schemes) |
| Path symbol resolution (`~/`, `$/`) | `(new Filesystem())->symbolizePath($path)` |
| `str_replace('\\', '/', $path)` (backslash → forward slash, often for cross-platform comparison) | `(new Filesystem())->normalizePath($path)` |
| `str_replace('/', DIRECTORY_SEPARATOR, $path)` (forward slash → native, when handing a path to an OS API) | `PathResolver::standardize($path)` |
| Atomic file write (avoid partial-write races) | `(new \Symfony\Component\Filesystem\Filesystem())->dumpFile($path, $contents)` |
| Find files matching a pattern | `\Symfony\Component\Finder\Finder::create()->files()->name('*.ext')->in($dir)` |
| `RecursiveDirectoryIterator` + filter loops | Same — `Finder` is almost always shorter and clearer |

Strings and arrays:

| If you reach for… | Use this instead |
|---|---|
| `preg_match('/^prefix/', $s)` | `Str::startsWith($s, 'prefix')` (accepts array of prefixes too) |
| Manual `strpos !== false` | `Str::contains($s, $needle)` |
| `strtolower`-then-replace slug generation | `Str::slug($s)` |
| Random hex/string for tmp files, tokens | `Str::random()` / `Str::uuid()` |
| Deep array key access with null safety | `data_get($array, 'a.b.c', $default)` (works on objects and arrays) |
| Pulling subset of array keys | `Arr::only($array, ['a', 'b'])` / `Arr::except($array, ['c'])` |
| Flattening nested array structure | `Arr::dot($array)` / `Arr::undot($array)` / `Arr::flatten($array)` |
| Chained map / filter / reduce on array | `collect($array)->filter(...)->map(...)->values()->all()` |

Other:

| If you reach for… | Use this instead |
|---|---|
| `exec()` / `shell_exec()` / backticks | `(new \Symfony\Component\Process\Process([$cmd, ...$args]))->mustRun()` |
| Manual YAML/JSON parsing | `\Symfony\Component\Yaml\Yaml::parse()` / `json_decode($s, true, 512, JSON_THROW_ON_ERROR)` |
| Generating UUID via `random_bytes()` + manual formatting | `Str::uuid()` or `\Symfony\Component\Uid\Uuid::v7()` |
| HTTP request to external service | Laravel's `Http::get(...)` facade (built on Guzzle) |

## When you genuinely need a new helper

1. **Grep first.** If the function is missing, document where you looked in the PR description.
2. **Put it in Storm**, not the consumer module, unless it has hard module-specific dependencies (theme/plugin/CMS concerns belong in `modules/`, not `vendor/winter/storm/`).
3. **Test the security-relevant edge cases.** For paths: traversal (`../../etc/passwd`), prefix collision (`/var/www/secret_other` vs allowed `/var/www/secret`), symlink escape, Windows separators.
4. **Use the canonical helpers internally** — don't have your new utility hand-roll its own `realpath()` and prefix check; compose from `PathResolver`.

## Tests

- Extend `TestCase` (Orchestra Testbench-based) for tests that need the Laravel container. Plain unit tests against pure helper classes don't need it.
- For fixtures that need to live inside the application root (Assetic's `FileAsset` enforces this), use `base_path('storage/framework/cache/...')` plus `bin2hex(random_bytes(4))` for uniqueness, then clean up with `Filesystem::deleteDirectory()` in `tearDown()`.
- Run the full suite (`vendor/bin/phpunit`) before claiming a change is regression-free. Failures unrelated to your change usually mean a stale `vendor/` — `composer update` and try again before blaming the test.

## Layering boundaries

Storm depends on Laravel and a few Symfony pieces; it must **not** depend on Winter modules (`modules/backend`, `modules/cms`, `modules/system`) or on plugin code. If a feature you're adding needs CMS-specific configuration (e.g., which directories count as "themes"), expose a setter on the Storm class and let the consumer in `modules/` supply the policy.
