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
    public function __construct(
        private readonly TagRepository $tagRepository
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            /** @var array<string> $tagIds */
            $tagIds = $event->getData();

            // this is mainly here, because the link from tags index page uses the non-array syntax
            if (\is_string($tagIds) || \is_int($tagIds)) {
                $tagIds = array_filter(array_unique(array_map('trim', explode(',', $tagIds))));
            }

            if (!\is_array($tagIds)) {
                return;
            }

            $tags = [];
            foreach ($tagIds as $tagId) {
                $tag = null;

                if (is_numeric($tagId)) {
                    $tag = $this->tagRepository->find($tagId);
                }

                if ($tag === null) {
                    $tag = $this->tagRepository->findTagByName($tagId);
                }

                if ($options['allow_create'] && $tag === null) {
                    $tag = new Tag();
                    $tag->setName($tagId);
                    $this->tagRepository->saveTag($tag);
                }

                if ($tag !== null) {
                    $tags[] = $tag->getId();
                }
            }

            $event->setData($tags);
        }, 1000);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'class' => Tag::class,
            'label' => 'tag',
            'allow_create' => false,
            'choice_value' => function (Tag $tag) {
                return $tag->getId();
            },
            'choice_attr' => function (Tag $tag) {
                return ['data-color' => $tag->getColorSafe()];
            },
            'choice_label' => function (Tag $tag) {
                return $tag->getName();
            },
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (TagRepository $repo) use ($options) {
                $query = new TagFormTypeQuery();
                $query->setUser($options['user']);

                return $repo->getQueryBuilderForFormType($query);
            };
        });

        $resolver->setAllowedTypes('allow_create', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['allow_create']) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-create' => 'post_tag',
            ]);
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-renderer' => 'color',
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
