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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class TagsType extends AbstractType
{
    public function __construct(
        private AuthorizationCheckerInterface $auth,
        private TagRepository $repository,
        private CacheInterface $cache
    )
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
        $tagsCount = $this->cache->get('tags_count', function (ItemInterface $item) {
            $item->expiresAfter(86400); // store it for one day, it doesn't need to be accurate

            return $this->repository->count([]);
        });

        if ($tagsCount > TagRepository::MAX_AMOUNT_SELECT) {
            return TagsInputType::class;
        }

        return TagsSelectType::class;
    }
}
