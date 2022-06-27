<?php namespace Winter\Storm\Translation;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Translation\Translator as TranslatorBase;

/**
 * Winter translator class.
 *
 * @package translation
 * @author Alexey Bobkov, Samuel Georges
 */
class Translator extends TranslatorBase
{
    const CORE_LOCALE = 'en';

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|\Winter\Storm\Events\Dispatcher
     */
    protected $events;

    /**
     * @var array List of namespace aliases. ['aliased.namespace' => 'real.namespace']
     */
    protected $aliases = [];

    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string|array|null
     */
    public function trans($key, array $replace = [], $locale = null)
    {
        return $this->get($key, $replace, $locale);
    }

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        /**
         * @event translator.beforeResolve
         * Fires before the translator resolves the requested language key
         *
         * >**NOTE:** It is highly recommended to use [project level localization overrides](https://wintercms.com/docs/plugin/localization#overriding) before reaching for this event.
         *
         * Example usage (overrides the value returned for a specific language key):
         *
         *     Event::listen('translator.beforeResolve', function ((string) $key, (array) $replace, (string|null) $locale) {
         *         if ($key === 'my.custom.key') {
         *             return 'My overriding value';
         *         }
         *     });
         *
         */
        if (isset($this->events) &&
            ($line = $this->events->fire('translator.beforeResolve', [$key, $replace, $locale], true))) {
            return $line;
        }

        if ($line = $this->getValidationSpecific($key, $replace, $locale)) {
            return $line;
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * Set the language string value for a given key in a given locale.
     *
     * If no locale is provided, the language string will be set for the default locale.
     */
    public function set(array|string $key, array|string|null $value = null, ?string $locale = null): void
    {
        if (is_array($key)) {
            foreach ($key as $itemKey => $itemValue) {
                $this->set($itemKey, $itemValue, $locale);
            }
        } else {
            $locale = $locale ?: $this->locale;

            $this->loaded['*']['*'][$locale][$key] = $value;
        }
    }

    /**
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function transChoice($key, $number, array $replace = [], $locale = null)
    {
        return $this->choice($key, $number, $replace, $locale);
    }

    /**
     * Check the system namespace by default for "validation" keys.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    protected function getValidationSpecific($key, $replace, $locale)
    {
        if (
            starts_with($key, 'validation.') &&
            !starts_with($key, 'validation.custom.') &&
            !starts_with($key, 'validation.attributes.')
        ) {
            $nativeKey = 'system::'.$key;
            $line = $this->get($nativeKey, $replace, $locale);
            if ($line !== $nativeKey) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function localeForChoice($locale)
    {
        $locale = parent::localeForChoice($locale);

        if (!is_null($locale) && str_contains($locale, '-')) {
            $localeParts = explode('-', $locale, 2);
            $locale = $localeParts[0] . '_' . strtoupper($localeParts[1]);
        }

        return $locale;
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = parent::parseKey($key);

        $namespace = strtolower($segments[0]);

        if (isset($this->aliases[$namespace])) {
            $segments[0] = $this->aliases[$namespace];
        }

        return $segments;
    }

    /**
     * Get the array of locales to be checked.
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray($locale)
    {
        $locales = array_filter([$locale ?: $this->locale, $this->fallback, static::CORE_LOCALE]);

        return call_user_func($this->determineLocalesUsing ?: fn () => $locales, $locales);
    }

    /**
     * Register a namespace alias.
     *
     * @param string $namespace The namespace to register an alias for. Example: winter.blog
     * @param string $alias The alias to register. Example: rainlab.blog
     * @return void
     */
    public function registerNamespaceAlias(string $namespace, string $alias)
    {
        $this->aliases[strtolower($alias)] = strtolower($namespace);
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }
}
