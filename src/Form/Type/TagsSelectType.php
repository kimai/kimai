<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Tag;
use App\Repository\Query\TagFormTypeQuery;
use App\Repository\TagRepository;
use App\Utils\Color;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a tag.
 */
final class TagsSelectType extends AbstractType
{
    public function __construct(private TagRepository $tagRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            if (!$options['allow_create']) {
                return;
            }
            $tagIds = $event->getData();
            if (!\is_array($tagIds)) {
                return;
            }
            $ids = array_filter($tagIds, function ($tagId) {
                if (is_numeric($tagId)) {
                    return true;
                }

                return false;
            });

            // get the current tags and find the new ones that should be created
            $tags = $this->tagRepository->findBy(['id' => $ids]);

            $foundIds = [];
            foreach ($tags as $tag) {
                $foundIds[] = (string) $tag->getId();
            }

            $newData = [];
            $newNames = [];
            foreach ($tagIds as $tag) {
                if (!\in_array($tag, $foundIds, true)) {
                    $newNames[] = $tag;
                } else {
                    $newData[] = $tag;
                }
            }

            // in case someone is using tags like "1234" this can interfere with the ID
            $tags = $this->tagRepository->findTagsByName($newNames);
            $foundTagNames = [];
            foreach ($tags as $tag) {
                $newData[] = (string) $tag->getId();
                $foundTagNames[] = $tag->getName();
            }

            /** @var array<string> $newNamesCreate */
            $newNamesCreate = array_udiff($newNames, $foundTagNames, function (mixed $userTag, mixed $existingTag) {
                if (!\is_string($userTag) || !\is_string($existingTag)) {
                    return -1;
                }

                if (mb_strtolower($userTag) === mb_strtolower($existingTag)) {
                    return 0;
                }

                return strcmp($userTag, $existingTag);
            });

            foreach ($newNamesCreate as $name) {
                $tag = new Tag();
                $tag->setName(mb_substr($name, 0, 100));
                $this->tagRepository->saveTag($tag);
                $newData[] = $tag->getId();
            }

            $event->setData($newData);
        }, 1000);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'class' => Tag::class,
            'label' => 'tag',
            'allow_create' => false,
            'choice_attr' => function (Tag $tag) {
                $color = $tag->getColor();
                if ($color === null) {
                    $color = (new Color())->getRandom($tag->getName());
                }

                return ['data-color' => $color];
            },
            'choice_label' => function (Tag $tag) {
                return $tag->getName();
            },
            'attr' => ['data-renderer' => 'color'],
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (TagRepository $repo) use ($options) {
                $query = new TagFormTypeQuery();
                $query->setUser($options['user']);

                return $repo->getQueryBuilderForFormType($query);
            };
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['allow_create']) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-create' => 'post_tag',
            ]);
        }
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
