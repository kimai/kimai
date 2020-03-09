<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait InvoiceSettingsTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="company", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(name="vat_id", type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    private $vatId;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="contact", type="text", nullable=true)
     */
    private $contact;

    /**
     * @var int
     *
     * @ORM\Column(name="due_days", type="integer", length=3, nullable=false)
     * @Assert\Range(min = 0, max = 999)
     */
    private $dueDays = 30;

    /**
     * @var float
     *
     * @ORM\Column(name="vat", type="float", nullable=false)
     * @Assert\Range(min = 0.0, max = 99.99)
     */
    private $vat = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="calculator", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=20)
     */
    private $calculator = 'default';
    /**
     * @var string
     *
     * @ORM\Column(name="number_generator", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=20)
     */
    private $numberGenerator = 'default';

    /**
     * @var string
     *
     * @ORM\Column(name="renderer", type="string", length=20, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=20)
     */
    private $renderer = 'default';

    /**
     * @var string
     *
     * @ORM\Column(name="payment_terms", type="text", nullable=true)
     */
    private $paymentTerms;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_details", type="text", nullable=true)
     */
    private $paymentDetails;

    /**
     * Used when rendering HTML templates.
     *
     * @var bool
     *
     * @ORM\Column(name="decimal_duration", type="boolean", nullable=false, options={"default": false})
     * @Assert\NotNull()
     */
    private $decimalDuration = false;

    /**
     * Used when rendering HTML templates.
     *
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=6, nullable=true)
     */
    private $language;
}
