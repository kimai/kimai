<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the customer.
 * @extends AbstractType<CustomerQuery>
 */
final class CustomerToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function __construct(private readonly CustomerRepository $customerRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchTermInputField($builder);

        // fetch countries
        $qb = $this->customerRepository->createQueryBuilder('c');
        $qb
            ->select('c.country')
            ->distinct(true);

        $countries = $qb->getQuery()->getSingleColumnResult();
        $choices = [];
        foreach ($countries as $country) {
            if (\is_string($country) && $country !== '' && \is_string($options['locale'])) {
                $choices[$country] = Countries::getName($country, $options['locale']);
            }
        }

        if (\count($choices) > 0) {
            $choices = array_flip($choices);
            ksort($choices);
            $builder->add('country', ChoiceType::class, [
                'label' => 'country',
                'choices' => $choices,
                'required' => false,
            ]);
        }

        $this->addVisibilityChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, CustomerQuery::CUSTOMER_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerQuery::class,
            'csrf_protection' => false,
            'locale' => locale_get_default(),
        ]);
        $resolver->setAllowedTypes('locale', ['string']);
    }
}
