<?php


namespace App\Services\Formaters;

/**
 * The ExtendedCurrencyFormatter class provides currency formatting capabilities for both standard
 * and extended currency codes. It uses the NumberFormatter to format currency values with the
 * appropriate currency symbol based on the length of the currency code.
 */
class ExtendedCurrencyFormatter
{
    private $numberFormatter;
    /**
     * @var string
     */
    private $pattern = '#,##0.00';
    private $formatter;
    /**
     * Create a new ExtendedCurrencyFormatter instance.
     */
    public function __construct()
    {
        // Create a new NumberFormatter instance for the "en-US" locale
        $this->numberFormatter = new \NumberFormatter('en-US', \NumberFormatter::CURRENCY);
        $this->formatter = new \NumberFormatter('en-US', \NumberFormatter::PATTERN_DECIMAL);
        $this->formatter->setPattern($this->pattern);
    }

    /**
     * Format a currency value with the appropriate currency symbol.
     *
     * If the currency code is longer than 3 characters, it uses the currency code as the symbol.
     * Otherwise, it uses the default behavior for 3-character currency codes.
     *
     * @param float $amount The currency amount to format.
     * @param string $currencyCode The currency code, e.g., "USD" or "USDC".
     * @return string The formatted currency value with the appropriate symbol.
     */
    public function formatCurrency($amount, $currencyCode)
    {
        // Check if the currency code is longer than 3 characters
        if (strlen($currencyCode) > 3) {
            // Use the currency code as the symbol
            return $currencyCode.' '.$this->formatter->format($amount);
        } else {
            // Use the default behavior for 3-character currency codes
            return $this->numberFormatter->formatCurrency($amount, $currencyCode);
        }
    }
}
