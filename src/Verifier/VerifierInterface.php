<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Verifier;

/**
 *
 * Password Verifier
 *
 * @package Aura.Auth
 *
 */
interface VerifierInterface
{
    /**
     *
     * @param string $plaintext Plaintext
     *
     * @param string $hashvalue encrypted string
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array());
}
