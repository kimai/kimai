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
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MultiUpdateTable extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var EntityRepository $repository */
        $repository = $options['repository'];
        /** @var MultiUpdateTableDTO $dto */
        $dto = $options['data'];

        $builder->add('entities', HiddenType::class, [
            'required' => false,
            'attr' => ['class' => 'multi_update_ids']
        ]);

        $builder->get('entities')->addModelTransformer(
            new CallbackTransformer(
                function ($ids): string {
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

        $i = 0;
        foreach ($dto->getActions() as $key => $value) {
            if (empty($key) || empty($value)) {
                continue;
            }
            $builder->add('action_' . $i++, ButtonType::class, [
                'label' => $key,
                'attr' => [
                    'data-href' => $value,
                    'class' => 'multi_update_table_action' . (stripos($key, 'delete') !== false ? ' btn-danger' : ''),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
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
