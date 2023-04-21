<?php namespace Winter\Storm\Html;

use Illuminate\Session\Store as Session;
use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

/**
 * Form builder
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class FormBuilder
{
    use \Illuminate\Support\Traits\Macroable;

    /**
     * The HTML builder instance.
     */
    protected \Winter\Storm\Html\HtmlBuilder $html;

    /**
     * The URL generator instance.
     */
    protected \Illuminate\Routing\UrlGenerator $url;

    /**
     * The CSRF token used by the form builder.
     */
    protected ?string $csrfToken = null;

    /**
     * The session store implementation.
     */
    protected ?\Illuminate\Session\Store $session = null;

    /**
     * The current model instance for the form.
     */
    protected object|array|null $model = null;

    /**
     * An array of label names we've created.
     */
    protected array $labels = [];

    /**
     * The reserved form open attributes.
     */
    protected array $reserved = [
        'method',
        'url',
        'route',
        'action',
        'files',
        'request',
        'model',
        'sessionKey'
    ];

    /**
     * The reserved form AJAX attributes.
     */
    protected array $reservedAjax = [
        'request',
        'success',
        'error',
        'complete',
        'confirm',
        'redirect',
        'update',
        'data',
        'validate',
        'flash'
    ];

    /**
     * The form methods that should be spoofed, in uppercase.
     */
    protected array $spoofedMethods = [
        'DELETE',
        'PATCH',
        'PUT'
    ];

    /**
     * The types of inputs to not fill values on by default.
     */
    protected array $skipValueTypes = [
        'file',
        'password',
        'checkbox',
        'radio'
    ];

    /**
     * The session key used by the form builder.
     */
    protected ?string $sessionKey = null;

    /**
     * Create a new form builder instance.
     */
    public function __construct(HtmlBuilder $html, UrlGeneratorBase $url, ?string $csrfToken = null, ?string $sessionKey = null)
    {
        $this->url = $url;
        $this->html = $html;
        $this->csrfToken = $csrfToken;
        $this->sessionKey = $sessionKey;
    }

    /**
     * Open up a new HTML form and includes a session key.
     */
    public function open(array $options = []): string
    {
        $method = strtoupper(array_get($options, 'method', 'post'));
        $request = array_get($options, 'request');
        $model = array_get($options, 'model');

        if ($model) {
            $this->model = $model;
        }

        $append = $this->requestHandler($request);

        if ($method != 'GET') {
            $append .= $this->sessionKey(array_get($options, 'sessionKey'));
        }

        $attributes = [];

        // We need to extract the proper method from the attributes. If the method is
        // something other than GET or POST we'll use POST since we will spoof the
        // actual method since forms don't support the reserved methods in HTML.
        $attributes['method'] = $this->getMethod($method);

        $attributes['action'] = $this->getAction($options);

        $attributes['accept-charset'] = 'UTF-8';

        // If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
        // field that will instruct the Symfony request to pretend the method is a
        // different method than it actually is, for convenience from the forms.
        $append .= $this->getAppendage($method);

        if (isset($options['files']) && $options['files']) {
            $options['enctype'] = 'multipart/form-data';
        }

        // Finally we're ready to create the final form HTML field. We will attribute
        // format the array of attributes. We will also add on the appendage which
        // is used to spoof requests for this PUT, PATCH, etc. methods on forms.
        $attributes = array_merge(
            $attributes,
            array_except($options, $this->reserved)
        );

        // Finally, we will concatenate all of the attributes into a single string so
        // we can build out the final form open statement. We'll also append on an
        // extra value for the hidden _method field if it's needed for the form.
        $attributes = $this->html->attributes($attributes);

        return '<form'.$attributes.'>'.$append;
    }

    /**
     * Helper for opening a form used for an AJAX call.
     */
    public function ajax(string|array $handler, array $options = []): string
    {
        if (is_array($handler)) {
            $handler = implode('::', $handler);
        }

        $attributes = array_merge(
            ['data-request' => $handler],
            array_except($options, $this->reservedAjax)
        );

        $ajaxAttributes = array_diff_key($options, $attributes);
        foreach ($ajaxAttributes as $property => $value) {
            $attributes['data-request-' . $property] = $value;
        }

        /*
         * The `files` option is a hybrid
         */
        if (isset($options['files'])) {
            $attributes['data-request-files'] = $options['files'];
        }

        return $this->open($attributes);
    }

    /**
     * Create a new model based form builder.
     */
    public function model(object|array $model, array $options = []): string
    {
        $this->model = $model;

        return $this->open($options);
    }

    /**
     * Set the model instance on the form builder.
     */
    public function setModel(object|array|null $model): void
    {
        $this->model = $model;
    }

    /**
     * Close the current form.
     */
    public function close(): string
    {
        $this->labels = [];

        $this->model = null;

        return '</form>';
    }

    /**
     * Generate a hidden field with the current CSRF token.
     */
    public function token(): string
    {
        $token = !empty($this->csrfToken)
            ? $this->csrfToken
            : ($this->session?->token() ?? null);

        return ($token) ? $this->hidden('_token', $token) : '';
    }

    /**
     * Create a form label element.
     */
    public function label(string $name, string $value = '', array $options = []): string
    {
        $this->labels[] = $name;

        $options = $this->html->attributes($options);

        $value = e($this->formatLabel($name, $value));

        return '<label for="'.$name.'"'.$options.'>'.$value.'</label>';
    }

    /**
     * Format the label value.
     */
    protected function formatLabel(string $name, string $value = ''): string
    {
        return $value ?: ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Create a form input field.
     *
     * @param string $type
     * @param string|null $name
     * @param string|null $value
     * @param array $options
     * @return string
     */
    public function input(string $type, ?string $name = null, ?string $value = null, array $options = []): string
    {
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        $merge = [
            'type' => $type,
        ];

        if (!empty($name)) {
            // We will get the appropriate value for the given field. We will look for the
            // value in the session for the value in the old input data then we'll look
            // in the model instance if one is set. Otherwise we will just use empty.
            $merge['id'] = $this->getIdAttribute($name, $options);
        }

        if (!in_array($type, $this->skipValueTypes)) {
            $merge['value'] = $this->getValueAttribute($name, $value);
        }

        $options = array_filter(array_merge($options, $merge), function ($item) {
            return (!is_null($item) && $item !== false);
        });

        return '<input' . $this->html->attributes($options) . '>';
    }

    /**
     * Create a text input field.
     */
    public function text(string $name, ?string $value = null, array $options = []): string
    {
        return $this->input('text', $name, $value, $options);
    }

    /**
     * Create a password input field.
     */
    public function password(string $name, array $options = []): string
    {
        return $this->input('password', $name, '', $options);
    }

    /**
     * Create a hidden input field.
     */
    public function hidden(string $name, ?string $value = null, array $options = []): string
    {
        return $this->input('hidden', $name, $value, $options);
    }

    /**
     * Create an email input field.
     */
    public function email(string $name, ?string $value = null, array $options = []): string
    {
        return $this->input('email', $name, $value, $options);
    }

    /**
     * Create a URL input field.
     */
    public function url(string $name, ?string $value = null, array $options = []): string
    {
        return $this->input('url', $name, $value, $options);
    }

    /**
     * Create a file input field.
     */
    public function file(string $name, array $options = []): string
    {
        return $this->input('file', $name, null, $options);
    }

    //
    // Textarea
    //

    /**
     * Create a textarea input field.
     */
    public function textarea(string $name, ?string $value = null, array $options = []): string
    {
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        // Next we will look for the rows and cols attributes, as each of these are put
        // on the textarea element definition. If they are not present, we will just
        // assume some sane default values for these attributes for the developer.
        $options = $this->setTextAreaSize($options);

        $options['id'] = $this->getIdAttribute($name, $options);

        $value = (string) $this->getValueAttribute($name, $value);

        unset($options['size']);

        // Next we will convert the attributes into a string form. Also we have removed
        // the size attribute, as it was merely a short-cut for the rows and cols on
        // the element. Then we'll create the final textarea elements HTML for us.
        $options = $this->html->attributes($options);

        return '<textarea'.$options.'>'.e($value).'</textarea>';
    }

    /**
     * Set the text area size on the attributes.
     */
    protected function setTextAreaSize(array $options): array
    {
        if (isset($options['size'])) {
            return $this->setQuickTextAreaSize($options);
        }

        // If the "size" attribute was not specified, we will just look for the regular
        // columns and rows attributes, using sane defaults if these do not exist on
        // the attributes array. We'll then return this entire options array back.
        $cols = array_get($options, 'cols', 50);

        $rows = array_get($options, 'rows', 10);

        return array_merge($options, compact('cols', 'rows'));
    }

    /**
     * Set the text area size using the quick "size" attribute.
     */
    protected function setQuickTextAreaSize(array $options): array
    {
        $segments = explode('x', $options['size']);

        return array_merge($options, ['cols' => $segments[0], 'rows' => $segments[1]]);
    }

    //
    // Select
    //

    /**
     * Create a select box field with empty option support.
     */
    public function select(string $name, array $list = [], string|array|null $selected = null, array $options = []): string
    {
        if (array_key_exists('emptyOption', $options)) {
            $list = ['' => $options['emptyOption']] + $list;
        }

        // When building a select box the "value" attribute is really the selected one
        // so we will use that when checking the model or session for a value which
        // should provide a convenient method of re-populating the forms on post.
        $selected = $this->getValueAttribute($name, $selected);

        $options['id'] = $this->getIdAttribute($name, $options);

        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        // We will simply loop through the options and build an HTML value for each of
        // them until we have an array of HTML declarations. Then we will join them
        // all together into one single HTML element that can be put on the form.
        $html = [];

        foreach ($list as $value => $display) {
            $html[] = $this->getSelectOption($display, $value, $selected);
        }

        // Once we have all of this HTML, we can join this into a single element after
        // formatting the attributes into an HTML "attributes" string, then we will
        // build out a final select statement, which will contain all the values.
        $options = $this->html->attributes($options);

        $list = implode('', $html);

        return "<select{$options}>{$list}</select>";
    }

    /**
     * Create a select range field.
     */
    public function selectRange(string $name, string|int|float $begin, string|int|float $end, string|array|null $selected = null, array $options = []): string
    {
        $range = array_combine($range = range($begin, $end), $range);

        return $this->select($name, $range, $selected, $options);
    }

    /**
     * Create a select year field.
     */
    public function selectYear(string $name, int $begin = 1900, ?int $end = null, string|array|null $selected = null, array $options = []): string
    {
        if (is_null($end)) {
            $end = (int) date('Y');
        }
        return $this->selectRange($name, $begin, $end, $selected, $options);
    }

    /**
     * Create a select month field.
     */
    public function selectMonth(string $name, string|array|null $selected = null, array $options = [], $format = '%B'): string
    {
        $months = [];

        foreach (range(1, 12) as $month) {
            $months[$month] = strftime($format, mktime(0, 0, 0, $month, 1));
        }

        return $this->select($name, $months, $selected, $options);
    }

    /**
     * Get the select option for the given value.
     */
    public function getSelectOption(string|array $display, string $value, string|array|null $selected = null): string
    {
        if (is_array($display)) {
            return $this->optionGroup($display, $value, $selected);
        }

        return $this->option($display, $value, $selected);
    }

    /**
     * Create an option group form element.
     */
    protected function optionGroup(array $list, string $label, string|array|null $selected = null): string
    {
        $html = [];

        foreach ($list as $value => $display) {
            $html[] = $this->option($display, $value, $selected);
        }

        return '<optgroup label="' . e($label) . '">' . implode('', $html) . '</optgroup>';
    }

    /**
     * Create a select element option.
     */
    protected function option(string $display, string $value, string|array|null $selected = null): string
    {
        $selectedAttr = $this->getSelectedValue($value, $selected);

        $options = [
            'value' => e($value),
            'selected' => $selectedAttr
        ];

        return '<option' . $this->html->attributes($options) . '>' . e($display) . '</option>';
    }

    /**
     * Determine if the value is selected.
     */
    protected function getSelectedValue(string $value, string|array|null $selected): string|null
    {
        if (is_null($selected)) {
            return null;
        }

        if (is_array($selected)) {
            return in_array($value, $selected) ? 'selected' : null;
        }

        return ((string) $value === (string) $selected) ? 'selected' : null;
    }

    //
    // Checkbox
    //

    /**
     * Create a checkbox input field.
     */
    public function checkbox(string $name, string $value = '1', bool $checked = false, array $options = []): string
    {
        return $this->checkable('checkbox', $name, $value, $checked, $options);
    }

    /**
     * Create a radio button input field.
     */
    public function radio(string $name, ?string $value = null, bool $checked = false, array $options = []): string
    {
        if (is_null($value)) {
            $value = $name;
        }

        return $this->checkable('radio', $name, $value, $checked, $options);
    }

    /**
     * Create a checkable input field.
     */
    protected function checkable(string $type, string $name, string $value, bool $checked = false, array $options = []): string
    {
        $checked = $this->getCheckedState($type, $name, $value, $checked);

        if ($checked) {
            $options['checked'] = 'checked';
        }

        return $this->input($type, $name, $value, $options);
    }

    /**
     * Get the check state for a checkable input.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $checked
     * @return bool
     */
    protected function getCheckedState($type, $name, $value, $checked)
    {
        switch ($type) {
            case 'checkbox':
                return $this->getCheckboxCheckedState($name, $value, $checked);

            case 'radio':
                return $this->getRadioCheckedState($name, $value, $checked);

            default:
                return $this->getValueAttribute($name) == $value;
        }
    }

    /**
     * Get the check state for a checkbox input.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @param  bool  $checked
     * @return bool
     */
    protected function getCheckboxCheckedState($name, $value, $checked)
    {
        if (
            isset($this->session) &&
            !$this->oldInputIsEmpty() &&
            is_null($this->old($name))
        ) {
            return false;
        }

        if ($this->missingOldAndModel($name)) {
            return $checked;
        }

        $posted = $this->getValueAttribute($name);

        return is_array($posted) ? in_array($value, $posted) : (bool) $posted;
    }

    /**
     * Get the check state for a radio input.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @param  bool  $checked
     * @return bool
     */
    protected function getRadioCheckedState($name, $value, $checked)
    {
        if ($this->missingOldAndModel($name)) {
            return $checked;
        }

        return $this->getValueAttribute($name) == $value;
    }

    /**
     * Determine if old input or model input exists for a key.
     *
     * @param  string  $name
     * @return bool
     */
    protected function missingOldAndModel($name)
    {
        return (is_null($this->old($name)) && is_null($this->getModelValueAttribute($name)));
    }

    /**
     * Create a HTML reset input element.
     *
     * @param  string  $value
     * @param  array   $attributes
     * @return string
     */
    public function reset($value, $attributes = [])
    {
        return $this->input('reset', null, $value, $attributes);
    }

    /**
     * Create a HTML image input element.
     *
     * @param  string  $url
     * @param  string  $name
     * @param  array   $attributes
     * @return string
     */
    public function image($url, $name = null, $attributes = [])
    {
        $attributes['src'] = $this->url->asset($url);

        return $this->input('image', $name, null, $attributes);
    }

    /**
     * Create a submit button element.
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function submit($value = null, $options = [])
    {
        return $this->input('submit', null, $value, $options);
    }

    /**
     * Create a button element.
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function button($value = null, $options = [])
    {
        if (!array_key_exists('type', $options)) {
            $options['type'] = 'button';
        }

        return '<button'.$this->html->attributes($options).'>'.$value.'</button>';
    }

    /**
     * Parse the form action method.
     *
     * @param  string  $method
     * @return string
     */
    protected function getMethod($method)
    {
        $method = strtoupper($method);

        return $method != 'GET' ? 'POST' : $method;
    }

    /**
     * Get the form action from the options.
     *
     * @param  array   $options
     * @return string
     */
    protected function getAction(array $options)
    {
        // We will also check for a "route" or "action" parameter on the array so that
        // developers can easily specify a route or controller action when creating
        // a form providing a convenient interface for creating the form actions.
        if (isset($options['url'])) {
            return $this->getUrlAction($options['url']);
        }

        if (isset($options['route'])) {
            return $this->getRouteAction($options['route']);
        }

        // If an action is available, we are attempting to open a form to a controller
        // action route. So, we will use the URL generator to get the path to these
        // actions and return them from the method. Otherwise, we'll use current.
        elseif (isset($options['action'])) {
            return $this->getControllerAction($options['action']);
        }

        return $this->url->current();
    }

    /**
     * Get the action for a "url" option.
     *
     * @param  array|string  $options
     * @return string
     */
    protected function getUrlAction($options)
    {
        if (is_array($options)) {
            return $this->url->to($options[0], array_slice($options, 1));
        }

        return $this->url->to($options);
    }

    /**
     * Get the action for a "route" option.
     *
     * @param  array|string  $options
     * @return string
     */
    protected function getRouteAction($options)
    {
        if (is_array($options)) {
            return $this->url->route($options[0], array_slice($options, 1));
        }

        return $this->url->route($options);
    }

    /**
     * Get the action for an "action" option.
     *
     * @param  array|string  $options
     * @return string
     */
    protected function getControllerAction($options)
    {
        if (is_array($options)) {
            return $this->url->action($options[0], array_slice($options, 1));
        }

        return $this->url->action($options);
    }

    /**
     * Get the form appendage for the given method.
     *
     * @param  string  $method
     * @return string
     */
    protected function getAppendage($method)
    {
        list($method, $appendage) = [strtoupper($method), ''];

        // If the HTTP method is in this list of spoofed methods, we will attach the
        // method spoofer hidden input to the form. This allows us to use regular
        // form to initiate PUT and DELETE requests in addition to the typical.
        if (in_array($method, $this->spoofedMethods)) {
            $appendage .= $this->hidden('_method', $method);
        }

        // If the method is something other than GET we will go ahead and attach the
        // CSRF token to the form, as this can't hurt and is convenient to simply
        // always have available on every form the developers creates for them.
        if ($method != 'GET') {
            $appendage .= $this->token();
        }

        return $appendage;
    }

    /**
     * Get the ID attribute for a field name.
     *
     * @param  string  $name
     * @param  array   $attributes
     * @return string|null
     */
    public function getIdAttribute($name, $attributes)
    {
        if (array_key_exists('id', $attributes)) {
            return $attributes['id'];
        }

        if (in_array($name, $this->labels)) {
            return $name;
        }

        return null;
    }

    /**
     * Get the value that should be assigned to the field.
     *
     * @param  string|null  $name
     * @param  string|array|int|null  $value
     * @return string|array|null
     */
    public function getValueAttribute(?string $name = null, $value = null)
    {
        if (empty($name)) {
            return $value;
        }

        if (!is_null($this->old($name))) {
            return $this->old($name);
        }

        if (!is_null($value)) {
            return $value;
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($name);
        }
    }

    /**
     * Get the model value that should be assigned to the field.
     *
     * @param  string  $name
     * @return string|array|null
     */
    protected function getModelValueAttribute($name)
    {
        if (is_object($this->model)) {
            return object_get($this->model, $this->transformKey($name));
        }
        elseif (is_array($this->model)) {
            return array_get($this->model, $this->transformKey($name));
        }
    }

    /**
     * Get a value from the session's old input.
     *
     * @param  string  $name
     * @return string|array|null
     */
    public function old($name)
    {
        if (isset($this->session)) {
            return $this->session->getOldInput($this->transformKey($name));
        }

        return null;
    }

    /**
     * Determine if the old input is empty.
     *
     * @return bool
     */
    public function oldInputIsEmpty()
    {
        return (isset($this->session) && count($this->session->getOldInput()) == 0);
    }

    /**
     * Transform key from array to dot syntax.
     *
     * @param  string  $key
     * @return string
     */
    protected function transformKey($key)
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }

    /**
     * Get the session store implementation.
     *
     * @return  \Illuminate\Session\Store  $session
     */
    public function getSessionStore()
    {
        return $this->session;
    }

    /**
     * Set the session store implementation.
     *
     * @param  \Illuminate\Session\Store  $session
     * @return $this
     */
    public function setSessionStore(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Helper for getting form values. Tries to find the old value,
     * then uses a postback/get value, then looks at the form model values.
     * @param  string $name
     * @param  string $value
     * @return string
     */
    public function value($name, $value = null)
    {
        if (empty($name)) {
            return $value;
        }

        if (!is_null($this->old($name))) {
            return $this->old($name);
        }

        if (!is_null(input($name, null))) {
            return input($name);
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($name);
        }

        return $value;
    }

    /**
     * Returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    protected function requestHandler($name = null)
    {
        if (!strlen($name)) {
            return '';
        }

        return $this->hidden('_handler', $name);
    }

    /**
     * Returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    public function sessionKey($sessionKey = null)
    {
        if (!$sessionKey) {
            $sessionKey = post('_session_key', $this->sessionKey);
        }

        return $this->hidden('_session_key', $sessionKey);
    }

    /**
     * Returns the active session key, used fr deferred bindings.
     * @return string|null
     */
    public function getSessionKey()
    {
        return $this->sessionKey;
    }
}
