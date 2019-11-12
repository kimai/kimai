<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\MultiUpdate;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiUpdateTable extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityRepository $repository */
        $repository = $options['repository'];
        /** @var MultiUpdateTableDTO $dto */
        $dto = $options['data'];

        $builder->add('entities', HiddenType::class, [
            'required' => false,
        ]);

        $builder->get('entities')->addModelTransformer(
            new CallbackTransformer(
                function ($ids) {
                    return implode(',', $ids);
                },
                function ($ids) use ($repository) {
                    if (empty($ids)) {
                        return [];
                    }

                    return $repository->matching((new Criteria())->where(Criteria::expr()->in('id', explode(',', $ids))));
                }
            )
        );

        $builder->add('action', ChoiceType::class, [
            'mapped' => false,
            'required' => false,
            'choices' => $dto->getActions(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['repository']);
        $resolver->setAllowedTypes('repository', EntityRepository::class);
        $resolver->setRequired('repository');

        $resolver->setDefaults([
            'data_class' => MultiUpdateTableDTO::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'entities_multiupdate',
        ]);
    }
}
