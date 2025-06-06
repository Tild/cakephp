<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

/**
 * StatusCodeBase
 *
 * @internal
 */
abstract class StatusCodeBase extends ResponseBase
{
    /**
     * @var array<int, int>|int
     */
    protected array|int $code;

    /**
     * Check assertion
     *
     * @param array<int, int>|int $other Array of min/max status codes, or a single code
     * @return bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function matches($other): bool
    {
        if (!$other) {
            $other = $this->code;
        }

        if (is_array($other)) {
            return $this->statusCodeBetween($other[0], $other[1]);
        }

        return $this->response->getStatusCode() === $other;
    }

    /**
     * Helper for checking status codes
     *
     * @param int $min Min status code (inclusive)
     * @param int $max Max status code (inclusive)
     * @return bool
     */
    protected function statusCodeBetween(int $min, int $max): bool
    {
        return $this->response->getStatusCode() >= $min && $this->response->getStatusCode() <= $max;
    }

    /**
     * Overwrites the descriptions so we can remove the automatic "expected" message
     *
     * @param mixed $other Value
     * @return string
     */
    protected function failureDescription(mixed $other): string
    {
        return $this->toString();
    }
}
