<?php namespace Winter\Storm\Foundation\Exception;

use Closure;
use Throwable;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Winter\Storm\Exception\AjaxException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        \Winter\Storm\Exception\AjaxException::class,
        \Winter\Storm\Exception\ValidationException::class,
        \Winter\Storm\Exception\ApplicationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ];

    /**
     * All of the register exception handlers.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Report or log an throwable.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $throwable
     * @return void
     */
    public function report(Throwable $throwable)
    {
        /**
         * @event exception.beforeReport
         * Fires before the exception has been reported
         *
         * Example usage (prevents the reporting of a given exception)
         *
         *     Event::listen('exception.report', function (\Throwable $throwable) {
         *         if ($throwable instanceof \My\Custom\Exception) {
         *             return false;
         *         }
         *     });
         */
        if (app()->make('events')->fire('exception.beforeReport', [$throwable], true) === false) {
            return;
        }

        if ($this->shouldntReport($throwable)) {
            return;
        }

        if (class_exists('Log')) {
            Log::error($throwable);
        }

        /**
         * @event exception.report
         * Fired after the exception has been reported
         *
         * Example usage (performs additional reporting on the exception)
         *
         *     Event::listen('exception.report', function (\Throwable $throwable) {
         *         app('sentry')->captureException($throwable);
         *     });
         */
        app()->make('events')->fire('exception.report', [$throwable]);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $throwable
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable  $throwable)
    {
        $statusCode = $this->getStatusCode($throwable);
        $response = $this->callCustomHandlers($throwable);

        if (!is_null($response)) {
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                return $response;
            }

            return Response::make($response, $statusCode);
        }

        if ($event = app()->make('events')->fire('exception.beforeRender', [$throwable, $statusCode, $request], true)) {
            return Response::make($event, $statusCode);
        }

        return parent::render($request, $throwable);
    }

    /**
     * Checks if the exception implements the HttpExceptionInterface, or returns
     * as generic 500 error code for a server side error.
     * @param \Throwable $throwable
     * @return int
     */
    protected function getStatusCode($throwable)
    {
        if ($throwable instanceof HttpExceptionInterface) {
            $code = $throwable->getStatusCode();
        }
        elseif ($throwable instanceof AjaxException) {
            $code = 406;
        }
        else {
            $code = 500;
        }

        return $code;
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context()
    {
        return [];
    }

    //
    // Custom handlers
    //

    /**
     * Register an application error handler.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        array_unshift($this->handlers, $callback);
    }

    /**
     * Handle the given throwable.
     *
     * @param  \Throwable  $throwable
     * @param  bool  $fromConsole
     * @return mixed|null
     */
    protected function callCustomHandlers($throwable, $fromConsole = false)
    {
        foreach ($this->handlers as $handler) {
            // If this throwable handler does not handle the given throwable, we will just
            // go the next one. A handler may type-hint an throwable that it handles so
            //  we can have more granularity on the error handling for the developer.
            if (!$this->handlesThrowable($handler, $throwable)) {
                continue;
            }

            $code = $this->getStatusCode($throwable);

            // We will wrap this handler in a try / catch and avoid white screens of death
            // if any throwables are thrown from a handler itself. This way we will get
            // at least some errors, and avoid errors with no data or not log writes.
            try {
                $response = $handler($throwable, $code, $fromConsole);
            } catch (Throwable $t) {
                $response = $this->convertExceptionToResponse($t);
            }

            // If this handler returns a "non-null" response, we will return it so it will
            // get sent back to the browsers. Once the handler returns a valid response
            // we will cease iterating through them and calling these other handlers.
            if (isset($response)) {
                return $response;
            }
        }
    }

    /**
     * Determine if the given handler handles this throwable.
     *
     * @param  \Closure    $handler
     * @param  \Throwable  $throwable
     * @return bool
     */
    protected function handlesThrowable(Closure $handler, $throwable)
    {
        $reflection = new ReflectionFunction($handler);
        return $reflection->getNumberOfParameters() == 0 || $this->hints($reflection, $throwable);
    }

    /**
     * Determine if the given handler type hints the throwable.
     *
     * @param  \ReflectionFunction  $reflection
     * @param  \Throwable  $throwable
     * @return bool
     */
    protected function hints(ReflectionFunction $reflection, $throwable)
    {
        $parameters = $reflection->getParameters();
        $expected = $parameters[0];

        if ($expected->getType() instanceof \ReflectionNamedType) {
            try {
                return (new ReflectionClass($expected->getType()->getName()))
                    ->isInstance($throwable);
            } catch (\Throwable $t) {
                return false;
            }
        } elseif ($expected->getType() instanceof \ReflectionUnionType) {
            foreach ($expected->getType()->getTypes() as $type) {
                try {
                    return (new ReflectionClass($type->getName()))
                        ->isInstance($throwable);
                } catch (\Throwable $t) {
                    return false;
                }
            }
        }

        return false;
    }
}
