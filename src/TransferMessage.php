<?php

namespace Wotz\BeBankTransferMessage;

use Wotz\BeBankTransferMessage\Exception\TransferMessageException;

class TransferMessage
{
    /**
     * Set divisor used to calculate the modulus
     */
    const MODULO = 97;

    /**
     * Set the asterisk sign as a circumfix
     */
    const CIRCUMFIX_ASTERISK = "*";

    /**
     * Set the plus sign as a circumfix
     */
    const CIRCUMFIX_PLUS = "+";

    /**
     * The number used to generate a structured message
     */
    private int $number;

    /**
     * The modulus resulting from the modulo operation
     */
    private int $modulus;

    /**
     * A structured message with a valid formatting
     */
    private ?string $structuredMessage = null;

    /**
     * @throws TransferMessageException
     */
    public function __construct(?int $number = null)
    {
        $this->setNumber($number);
        $this->generate();
    }

    /**
     * Generate a valid structured message based on the number
     */
    public function generate(string $circumfix = self::CIRCUMFIX_PLUS): ?string
    {
        $this->modulus = $this->mod($this->number);

        $structuredMessage = str_pad((string) $this->number, 10, '0', STR_PAD_LEFT)
            . str_pad((string) $this->modulus, 2, '0', STR_PAD_LEFT);

        $pattern = ['/^([0-9]{3})([0-9]{4})([0-9]{5})$/'];
        $replace = [str_pad('$1/$2/$3', 14, $circumfix, STR_PAD_BOTH)];

        return $this->structuredMessage = preg_replace($pattern, $replace, $structuredMessage);
    }

    /**
     * The mod97 calculation
     *
     * If the modulus is 0, the result is substituted to 97
     */
    private function mod(int $dividend): int
    {
        $modulus = $dividend % self::MODULO;

        return ($modulus > 0) ? $modulus : self::MODULO;
    }

    /**
     * Get the number
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Set the number
     *
     * If no number is passed to this method, a random number will be generated
     *
     * @throws TransferMessageException If the number is out of bounds
     */
    public function setNumber(?int $number): void
    {
        if (is_null($number)) {
            $this->number = random_int(1, 9999999999);
        } else {
            if (($number < 1) || ($number > 9999999999)) {
                throw new TransferMessageException(
                    'The number should be an integer larger then 0 and smaller then 9999999999.'
                );
            }

            $this->number = $number;
        }
    }

    /**
     * Get the modulus
     */
    public function getModulus(): int
    {
        return $this->modulus;
    }

    /**
     * Get the structured message
     */
    public function getStructuredMessage(): ?string
    {
        return $this->structuredMessage;
    }

    /**
     * Set a structured message
     *
     * @throws  TransferMessageException If the format is not valid
     */
    public function setStructuredMessage(string $structuredMessage): void
    {
        $pattern = '/^[\+\*]{3}[0-9]{3}[\/]?[0-9]{4}[\/]?[0-9]{5}[\+\*]{3}$/';

        if (preg_match($pattern, $structuredMessage) === 0) {
            throw new TransferMessageException('The structured message does not have a valid format.');
        } else {
            $this->structuredMessage = $structuredMessage;
        }
    }

    /**
     * Validates a structured message
     *
     * The validation is the mod97 calculation of the number and comparison of
     * the result to the provided modulus.
     */
    public function validate(): bool
    {
        $pattern = ['/^[\+\*]{3}([0-9]{3})[\/]?([0-9]{4})[\/]?([0-9]{5})[\+\*]{3}$/'];
        $replace = ['${1}${2}${3}'];

        if (! $this->structuredMessage) {
            return false;
        }

        $rawStructuredMessage = preg_replace($pattern, $replace, $this->structuredMessage);

        if (! $rawStructuredMessage) {
            return false;
        }

        $number = (int) substr($rawStructuredMessage, 0, 10);
        $modulus = (int) substr($rawStructuredMessage, 10, 2);

        return $modulus === $this->mod($number);
    }
}
