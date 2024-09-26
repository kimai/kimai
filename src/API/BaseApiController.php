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
use App\Utils\Pagination;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * @method null|User getUser()
 */
abstract class BaseApiController extends AbstractController
{
    public const DATE_ONLY_FORMAT = 'yyyy-MM-dd';
    public const DATE_FORMAT = DateTimeType::HTML5_FORMAT;
    public const DATE_FORMAT_PHP = 'Y-m-d\TH:i:s';

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

    protected function prepareQuery(BaseQuery $query, ParamFetcherInterface $paramFetcher): void
    {
        $query->setIsApiCall(true);
        $query->setCurrentUser($this->getUser());

        // there is no function has() in ParamFetcherInterface, so we need to use all() and check for the key
        $all = $paramFetcher->all(true);

        if (\array_key_exists('page', $all)) {
            $page = $all['page'];
            if (is_numeric($page)) {
                $query->setPage((int) $page);
            }
        }

        if (\array_key_exists('size', $all)) {
            $size = $all['size'];
            if (is_numeric($size)) {
                $query->setPageSize((int) $size);
            }
        }

        if (\array_key_exists('pageSize', $all)) {
            $size = $all['pageSize'];
            if (is_numeric($size)) {
                $query->setPageSize((int) $size);
            }
        }
    }

    protected function createPaginatedView(Pagination $pagination): View
    {
        $results = (array) $pagination->getCurrentPageResults();

        $view = new View($results, 200);
        $this->addPagination($view, $pagination);

        return $view;
    }

    protected function addPagination(View $view, Pagination $pagination): void
    {
        $view->setHeader('X-Page', (string) $pagination->getCurrentPage());
        $view->setHeader('X-Total-Count', (string) $pagination->getNbResults());
        $view->setHeader('X-Total-Pages', (string) $pagination->getNbPages());
        $view->setHeader('X-Per-Page', (string) $pagination->getMaxPerPage());
    }
}
