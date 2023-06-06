<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;
use Winter\Storm\Support\Testing\Fakes\MailFake;

/**
 * @method static \Illuminate\Mail\PendingMail to($users)
 * @method static \Illuminate\Mail\PendingMail bcc($users)
 * @method static void raw(string $text, $callback)
 * @method static void send(\Illuminate\Contracts\Mail\Mailable|string|array $view, array $data = [], \Closure|string $callback = null)
 * @method static array failures()
 * @method static mixed queue(\Illuminate\Contracts\Mail\Mailable|string|array $view, string $queue = null)
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, \Illuminate\Contracts\Mail\Mailable|string|array $view, string $queue = null)
 * @method static void assertSent(string $mailable, callable|int $callback = null)
 * @method static void assertNotSent(string $mailable, callable|int $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertQueued(string $mailable, callable|int $callback = null)
 * @method static void assertNotQueued(string $mailable, callable $callback = null)
 * @method static void assertNothingQueued()
 * @method static \Illuminate\Support\Collection sent(string $mailable, \Closure|string $callback = null)
 * @method static bool hasSent(string $mailable)
 * @method static \Illuminate\Support\Collection queued(string $mailable, \Closure|string $callback = null)
 * @method static bool hasQueued(string $mailable)
 *
 * @see \Winter\Storm\Mail\Mailer
 * @see \Winter\Storm\Support\Testing\Fakes\MailFake
 */
class Mail extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Winter\Storm\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        static::swap($fake = new MailFake(static::getFacadeRoot()));

        return $fake;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mailer';
    }
}
