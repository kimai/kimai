<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\SecurityPolicy;

use App\Entity\MetaTableTypeInterface;
use App\Entity\User;
use App\Pdf\PdfContext;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * The Twig environment needs the sandbox extension, which itself needs a policy to start working.
 */
final class DefaultPolicy implements SecurityPolicyInterface
{
    public function checkSecurity($tags, $filters, $functions): void
    {
    }

    public function checkMethodAllowed($obj, $method): void
    {
        if ($obj instanceof ServerBag) {
            throw new SecurityNotAllowedMethodError('Tried to access server environment', ServerBag::class, $method);
        }

        if ($obj instanceof SessionInterface) {
            throw new SecurityNotAllowedMethodError('Tried to access session', SessionInterface::class, $method);
        }

        $lcm = strtolower($method);

        if ($obj instanceof PdfContext) {
            if ($lcm !== 'setoption') {
                throw new SecurityNotAllowedMethodError('Tried to access forbidden method on PdfContext', PdfContext::class, $method);
            }

            return;
        }

        if ($obj instanceof MetaTableTypeInterface && $lcm === 'merge') {
            return;
        }

        if ($obj instanceof Request) {
            if (!str_starts_with($lcm, 'get')) {
                throw new SecurityNotAllowedMethodError('Tried to call setter() of app variable', AppVariable::class, $method);
            }

            return;
        }

        if ($obj instanceof AppVariable) {
            if (!\in_array($lcm, ['getrequest', 'getuser', 'getlocale'], true)) {
                throw new SecurityNotAllowedMethodError('Tried to access forbidden app variable method', User::class, $method);
            }

            return;
        }

        if (!str_starts_with($lcm, 'has') && !str_starts_with($lcm, 'is') && !str_starts_with($lcm, 'get') && $lcm !== '__tostring') {
            throw new SecurityNotAllowedMethodError('Tried to access non-read method', $obj::class, $method);
        }

        if ($obj instanceof User) {
            if (\in_array($lcm, ['getpassword', 'gettotpsecret', 'getplainpassword', 'getconfirmationtoken', 'gettotpauthenticationconfiguration'], true)) {
                throw new SecurityNotAllowedMethodError('Tried to access user secrets', User::class, $method);
            }
        }
    }

    public function checkPropertyAllowed($obj, $property): void
    {
    }
}
