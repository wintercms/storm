<?php namespace Winter\Storm\Mail;

use Winter\Storm\Support\Facades\Config;
use Winter\Storm\Support\Facades\Event;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Mailer as MailerBase;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Collection;

/**
 * Mailer class for sending mail.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Mailer extends MailerBase
{
    use \Winter\Storm\Support\Traits\Emitter;

    /**
     * @var string Original driver before pretending.
     */
    protected $pretendingOriginal;

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string|array  $view
     * @param  mixed  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function raw($view, $callback)
    {
        if (!is_array($view)) {
            $view = ['raw' => $view];
        }
        elseif (!array_key_exists('raw', $view)) {
            $view['raw'] = true;
        }

        return $this->send($view, [], $callback);
    }

    /**
     * Send a new message using a view.
     * Overrides the Laravel defaults to provide the following functionality:
     * - Events (global & local):
     *  - mailer.beforeSend
     *  - mailer.prepareSend
     *  - mailer.send
     * - Custom addContent() behavior
     * - Support for bypassing all addContent behavior when passing $view['raw' => true]
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($view, array $data = [], $callback = null)
    {
        /**
         * @event mailer.beforeSend
         * Fires before the mailer processes the sending action
         *
         * Example usage (stops the sending process):
         *
         *     Event::listen('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
         */
        if (
            ($this->fireEvent('mailer.beforeSend', [$view, $data, $callback], true) === false) ||
            (Event::fire('mailer.beforeSend', [$view, $data, $callback], true) === false)
        ) {
            return null;
        }

        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        if ($callback !== null) {
            call_user_func($callback, $message);
        }

        // When $raw === true, attach the content directly to the
        // message without any form of parsing or events being fired.
        // @see https://github.com/wintercms/storm/commit/7fdc46cb6c2424436b1eb1cb1a66223785d7520f
        // @see https://github.com/wintercms/storm/commit/aa1e96c5741f14900311daa2cad3826aaf97f6c8
        if (is_bool($raw) && $raw === true) {
            $this->addContentRaw($message, $view, $plain);
        } else {
            $this->addContent($message, $view, $plain, $raw, $data);
        }

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

         /**
          * @event mailer.prepareSend
          * Fires before the mailer processes the sending action
          *
          * Parameters:
          * - $view: View code as a string
          * - $message: Illuminate\Mail\Message object, check Symfony\Component\Mime\Email for useful functions.
          * - $data: Array
          *
          * Example usage (stops the sending process):
          *
          *     Event::listen('mailer.prepareSend', function ((\Winter\Storm\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message, (array) $data) {
          *         return false;
          *     });
          *
          * Or
          *
          *     $mailerInstance->bindEvent('mailer.prepareSend', function ((string) $view, (\Illuminate\Mail\Message) $message, (array) $data) {
          *         return false;
          *     });
          *
          */
        if (
            ($this->fireEvent('mailer.prepareSend', [$view, $message, $data], true) === false) ||
            (Event::fire('mailer.prepareSend', [$this, $view, $message, $data], true) === false)
        ) {
            return null;
        }



        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        $symfonyMessage = $message->getSymfonyMessage();

        $sentMessage = null;
        if ($this->shouldSendMessage($symfonyMessage, $data)) {
            $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);

            if ($symfonySentMessage) {
                $sentMessage = new SentMessage($symfonySentMessage);

                $this->dispatchSentEvent($sentMessage, $data);

                /**
                 * @event mailer.send
                 * Fires after the message has been sent
                 *
                 * Example usage (logs the message):
                 *
                 *     Event::listen('mailer.send', function ((\Winter\Storm\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message, (array) $data) {
                 *         \Log::info("Message was rendered with $view and sent");
                 *     });
                 *
                 * Or
                 *
                 *     $mailerInstance->bindEvent('mailer.send', function ((string) $view, (\Illuminate\Mail\Message) $message, (array) $data) {
                 *         \Log::info("Message was rendered with $view and sent");
                 *     });
                 *
                 */
                $this->fireEvent('mailer.send', [$view, $message, $data]);
                Event::fire('mailer.send', [$this, $view, $message, $data]);

                return $sentMessage;
            }
        }
    }

    /**
     * Add the content to a given message.
     * Overrides the Laravel defaults to provide the following functionality:
     * - Events (global & local):
     *  - mailer.beforeAddContent
     *  - mailer.addContent
     * - Support for the Winter MailParser
     *
     * @param  \Illuminate\Mail\Message $message
     * @param  string|null $view
     * @param  string|null $plain
     * @param  string|null $raw
     * @param  array|null $data
     * @return void
     */
    protected function addContent($message, $view = null, $plain = null, $raw = null, $data = null)
    {
        /**
         * @event mailer.beforeAddContent
         * Fires before the mailer adds content to the message
         *
         * Example usage (stops the content adding process):
         *
         *     Event::listen('mailer.beforeAddContent', function ((\Winter\Storm\Mail\Mailer) $mailerInstance, (\Illuminate\Mail\Message) $message, (string) $view, (array) $data, (string) $raw, (string) $plain) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.beforeAddContent', function ((\Illuminate\Mail\Message) $message, (string) $view, (array) $data, (string) $raw, (string) $plain) {
         *         return false;
         *     });
         *
         */
        if (
            ($this->fireEvent('mailer.beforeAddContent', [$message, $view, $data, $raw, $plain], true) === false) ||
            (Event::fire('mailer.beforeAddContent', [$this, $message, $view, $data, $raw, $plain], true) === false)
        ) {
            return;
        }

        $html = null;
        $text = null;

        if (isset($view)) {
            $viewContent = $this->renderView($view, $data);
            $result = MailParser::parse($viewContent);
            $html = $result['html'];

            if ($result['text']) {
                $text = $result['text'];
            }

            /*
             * Subject
             */
            $customSubject = $message->getSymfonyMessage()->getSubject();
            if (
                empty($customSubject) &&
                ($subject = array_get($result['settings'], 'subject'))
            ) {
                $message->subject($subject);
            }
        }

        if (isset($plain)) {
            $text = $this->renderView($plain, $data);
        }

        if (isset($raw)) {
            $text = $raw;
        }

        $this->addContentRaw($message, $html, $text);

        /**
         * @event mailer.addContent
         * Fires after the mailer has added content to the message
         *
         * Example usage (Logs that content has been added):
         *
         *     Event::listen('mailer.addContent', function ((\Winter\Storm\Mail\Mailer) $mailerInstance, (\Illuminate\Mail\Message) $message, (string) $view, (array) $data) {
         *         \Log::info("$view has had content added to the message");
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.addContent', function ((\Illuminate\Mail\Message) $message, (string) $view, (array) $data) {
         *         \Log::info("$view has had content added to the message");
         *     });
         *
         */
        $this->fireEvent('mailer.addContent', [$message, $view, $data]);
        Event::fire('mailer.addContent', [$this, $message, $view, $data]);
    }

    /**
     * Add the raw content to the provided message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string|null  $html
     * @param  string|null  $text
     * @return void
     */
    protected function addContentRaw($message, $html = null, $text = null)
    {
        if (isset($html)) {
            $message->html($html);
        }

        if (isset($text)) {
            $message->text($text);
        }
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  MailableContract|string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof MailableContract) {
            $mailable = $this->buildQueueMailable($view, $data, $callback, $queue);
            $queue = null;
        } else {
            $mailable = $view;
            $queue = $queue ?? $data;
        }

        return parent::queue($mailable, $queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function queueOn($queue, $view, $data = null, $callback = null)
    {
        return $this->queue($view, $data, $callback, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  int  $delay
     * @param  MailableContract|string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof MailableContract) {
            $mailable = $this->buildQueueMailable($view, $data, $callback, $queue);
            $queue = null;
        } else {
            $mailable = $view;
            $queue = $queue ?? $data;
        }

        return parent::later($delay, $mailable, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function laterOn($queue, $delay, $view, array $data = null, $callback = null)
    {
        return $this->later($delay, $view, $data, $callback, $queue);
    }

    /**
     * Build the mailable for a queued e-mail job.
     *
     * @param  mixed  $callback
     * @return mixed
     */
    protected function buildQueueMailable($view, $data, $callback, $queueName = null)
    {
        $mailable = new Mailable;

        if (!empty($queueName)) {
            $mailable->queue = $queueName;
        }

        $mailable->view($view)->withSerializedData($data);

        if ($callback !== null) {
            call_user_func($callback, $mailable);
        }

        return $mailable;
    }

    /**
     * Helper for raw() method, send a new message when only a raw text part.
     * @param  array $recipients
     * @param  array|string  $view
     * @param  mixed   $callback
     * @param  array   $options
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function rawTo($recipients, $view, $callback = null, $options = [])
    {
        if (!is_array($view)) {
            $view = ['raw' => $view];
        } elseif (!array_key_exists('raw', $view)) {
            $view['raw'] = true;
        }

        return $this->sendTo($recipients, $view, [], $callback, $options);
    }

    /**
     * Helper for send() method, the first argument can take a single email or an
     * array of recipients where the key is the address and the value is the name.
     *
     * @param  array $recipients
     * @param  string|array $view
     * @param  array $data
     * @param  mixed $callback
     * @param  array $options
     * @return mixed
     */
    public function sendTo($recipients, $view, array $data = [], $callback = null, $options = [])
    {
        if ($callback && !$options && !is_callable($callback)) {
            $options = $callback;
        }

        if (is_bool($options)) {
            $queue = $options;
            $bcc = false;
        } else {
            $queue = (bool) ($options['queue'] ?? false);
            $bcc = (bool) ($options['bcc'] ?? false);
        }

        $method = $queue === true ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        return $this->{$method}($view, $data, function ($message) use ($recipients, $callback, $bcc) {
            $method = $bcc === true ? 'bcc' : 'to';

            foreach ($recipients as $address => $name) {
                $message->{$method}($address, $name);
            }

            if (is_callable($callback)) {
                $callback($message);
            }
        });
    }

    /**
     * Process a recipients object, which can look like the following:
     *  - (string) 'admin@domain.tld'
     *  - (array) ['admin@domain.tld', 'other@domain.tld']
     *  - (object) ['email' => 'admin@domain.tld', 'name' => 'Adam Person']
     *  - (array) ['admin@domain.tld' => 'Adam Person', ...]
     *  - (array) [ (object|array) ['email' => 'admin@domain.tld', 'name' => 'Adam Person'], [...] ]
     * @param mixed $recipients
     * @return array
     */
    protected function processRecipients($recipients)
    {
        $result = [];

        if (is_string($recipients)) {
            $result[$recipients] = null;
        } elseif (is_array($recipients) || $recipients instanceof Collection) {
            foreach ($recipients as $address => $person) {
                if (is_int($address) && is_string($person)) {
                    // no name provided, only email address
                    $result[$person] = null;
                } elseif (is_string($person)) {
                    $result[$address] = $person;
                } elseif (is_object($person)) {
                    if (empty($person->email) && empty($person->address)) {
                        continue;
                    }

                    $address = !empty($person->email) ? $person->email : $person->address;
                    $name = !empty($person->name) ? $person->name : null;
                    $result[$address] = $name;
                } elseif (is_array($person)) {
                    if (!$address = array_get($person, 'email', array_get($person, 'address'))) {
                        continue;
                    }

                    $result[$address] = array_get($person, 'name');
                }
            }
        } elseif (is_object($recipients)) {
            if (!empty($recipients->email) || !empty($recipients->address)) {
                $address = !empty($recipients->email) ? $recipients->email : $recipients->address;
                $name = !empty($recipients->name) ? $recipients->name : null;
                $result[$address] = $name;
            }
        }

        return $result;
    }

    /**
     * Tell the mailer to not really send messages.
     *
     * @param  bool  $value
     * @return void
     */
    public function pretend($value = true)
    {
        if ($value) {
            $this->pretendingOriginal = Config::get('mail.default', 'smtp');
            Config::set('mail.default', 'log');
        } else {
            Config::set('mail.default', $this->pretendingOriginal);
        }
    }
}
