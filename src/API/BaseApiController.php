<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\User;
use App\Repository\Query\BaseQuery;
use App\Timesheet\DateTimeFactory;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

abstract class BaseApiController extends AbstractController
{
    public const MAX_PAGE_SIZE = 500;
    public const DATE_ONLY_FORMAT = 'yyyy-MM-dd';
    public const DATE_FORMAT = DateTimeType::HTML5_FORMAT;
    public const DATE_FORMAT_PHP = 'Y-m-d\TH:i:s';

    protected function getUser(): User
    {
        $user = parent::getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Need a user for API access');
        }

        return $user;
    }

    /**
     * @template TFormType of FormTypeInterface<TData>
     * @template TData of BaseQuery
     * @param class-string<TFormType> $type
     * @param TData $data
     * @param array<mixed> $options
     * @return FormInterface<BaseQuery>
     */
    protected function createSearchForm(string $type, BaseQuery $data, array $options = []): FormInterface
    {
        return $this->container
            ->get('form.factory')
            ->createNamed('', $type, $data, array_merge(['method' => 'GET'], $options));
    }

    protected function getDateTimeFactory(?User $user = null): DateTimeFactory
    {
        if (null === $user) {
            $user = $this->getUser();
        }

        return DateTimeFactory::createByUser($user);
    }

    /**
     * @template T of BaseQuery
     * @param T $query
     * @param ParamFetcherInterface $paramFetcher
     * @return T
     */
    protected function prepareQuery(BaseQuery $query, ParamFetcherInterface $paramFetcher): BaseQuery
    {
        $query->setIsApiCall(true);
        $query->setCurrentUser($this->getUser());

        // there is no function has() in ParamFetcherInterface, so we need to use all() and check for the key
        $all = $paramFetcher->all();

        if (\array_key_exists('page', $all)) {
            $page = $all['page'];
            if (is_numeric($page)) {
                $query->setPage((int) $page);
            }
        }

        if (\array_key_exists('size', $all)) {
            $size = $all['size'];
            if (is_numeric($size)) {
                $size = (int) $size;
                if ($size < 1) {
                    $size = BaseQuery::DEFAULT_PAGESIZE;
                }
                if ($size > self::MAX_PAGE_SIZE) {
                    $size = self::MAX_PAGE_SIZE;
                }
                $query->setPageSize($size);
            }
        }

        if (\array_key_exists('order', $all)) {
            $order = $all['order'];
            if (\is_string($order) && $order !== '') {
                $query->setOrder($order);
            }
        }

        if (\array_key_exists('orderBy', $all)) {
            $orderBy = $all['orderBy'];
            if (\is_string($orderBy) && $orderBy !== '') {
                $query->setOrderBy($orderBy);
            }
        }

        return $query;
    }
}
