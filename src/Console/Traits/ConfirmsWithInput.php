<?php namespace Winter\Storm\Console\Traits;

/**
 * Console Command Trait that provides confirmation step that requires set
 * input to be provided in order to act as confirmation for an action
 *
 * @package winter\storm
 * @author Luke Towers
 */
trait ConfirmsWithInput
{
    public function confirmWithInput(string $message, string $requiredInput): bool
    {
        if ($this->laravel->isProduction() && !$this->option('force')) {
            $this->error("THE APPLICATION IS IN PRODUCTION");
        }

        $this->alert($message);

        $confirmed = false;

        if ($this->option('force')) {
            $this->warn("The --force option was provided, proceeding without confirmation...");
            $confirmed = true;
        } else {
            $prompt = "Please type \"$requiredInput\" to proceed or CANCEL to cancel";
            do {
                $input = $this->ask($prompt);
                if (strtolower($input) === 'cancel') {
                    $confirmed = false;
                    break;
                }
                if (strtolower($input) === strtolower($requiredInput)) {
                    $confirmed = true;
                }
            } while ($confirmed === false);
        }

        return $confirmed;
    }
}
