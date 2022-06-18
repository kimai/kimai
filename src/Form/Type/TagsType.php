<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Repository\TagRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class TagsType extends AbstractType
{
    public function __construct(private AuthorizationCheckerInterface $auth, private TagRepository $repository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_create' => $this->auth->isGranted('create_tag'),
        ]);
    }

    public function getParent(): string
    {
        if ($this->repository->count([]) > TagRepository::MAX_AMOUNT_SELECT) {
            return TagsInputType::class;
        }

        return TagsSelectType::class;
    }
}
