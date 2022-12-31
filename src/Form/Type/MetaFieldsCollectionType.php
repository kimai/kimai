<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\MetaTableTypeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to edit entity meta fields.
 */
final class MetaFieldsCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var ArrayCollection<MetaTableTypeInterface> $collection */
                $collection = $event->getData();
                foreach ($collection as $collectionItem) {
                    $collection->removeElement($collectionItem);

                    if (!($collectionItem instanceof MetaTableTypeInterface)) {
                        continue;
                    }

                    // prevents unconfigured values from showing up in the form
                    if (null === $collectionItem->getType()) {
                        continue;
                    }

                    $collection->set($collectionItem->getName(), $collectionItem);
                }
            },
            // must be a higher priority then the listener in EntityMetaDefinitionType
            100
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => EntityMetaDefinitionType::class,
            'entry_options' => ['label' => false],
            'allow_add' => false,
            'allow_delete' => false,
            'label' => false,
        ]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }
}
