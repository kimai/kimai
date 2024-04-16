<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\LocaleService;
use App\Repository\Query\BaseQuery;
use App\Utils\DataTable;
use App\Utils\LocaleFormatter;
use App\Utils\PageSetup;
use App\Utils\Pagination;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/help')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class HelpController extends AbstractController
{
    #[Route(path: '/locales', defaults: [], name: 'help_locales', methods: ['GET'])]
    public function helpLocale(Request $request, LocaleService $service): Response
    {
        $table = new DataTable('help_locales', new BaseQuery());
        $table->addColumn('name', ['class' => 'alwaysVisible', 'orderBy' => false]);
        $table->addColumn('description', ['class' => 'd-none', 'orderBy' => false]);
        $table->addColumn('language', ['class' => 'd-none w-min', 'orderBy' => false]);
        $table->addColumn('date', ['class' => 'alwaysVisible w-min', 'orderBy' => false]);
        $table->addColumn('time', ['class' => 'alwaysVisible w-min text-center', 'orderBy' => false]);
        $table->addColumn('duration', ['class' => 'd-none w-min text-end', 'orderBy' => false]);
        $table->addColumn('decimal', ['class' => 'd-none w-min text-end', 'orderBy' => false, 'title' => 'Decimal']);
        $table->addColumn('money', ['class' => 'w-min text-end', 'orderBy' => false, 'title' => 'rate']);
        $table->addColumn('hour_24', ['class' => 'alwaysVisible w-min text-center', 'orderBy' => false]);
        $table->addColumn('rtl', ['class' => 'd-none w-min text-center', 'orderBy' => false, 'title' => 'RTL']);

        $page = new PageSetup('help_locales');
        $page->setDataTable($table);
        $page->setActionName('help_locales');

        $data = $this->buildLocales($request, $service);
        $pagination = new Pagination(new ArrayAdapter($data));
        $pagination->setMaxPerPage(9999);
        $table->setPagination($pagination);

        return $this->render('help/index.html.twig', [
            'page_setup' => $page,
            'dataTable' => $table,
        ]);
    }

    /**
     * @param Request $request
     * @param LocaleService $service
     * @return array<string, array<string, string|bool|null>>
     */
    private function buildLocales(Request $request, LocaleService $service): array
    {
        $requestLocale = $request->getLocale();
        $data = [];
        $now = $this->getDateTimeFactory()->createDateTime();

        foreach ($service->getAllLocales() as $locale) {
            $formatter = new LocaleFormatter($service, $locale);
            $data[$locale] = [
                'language' => $locale,
                'name' => Locales::getName($locale, $locale),
                'description' => Locales::getName($locale, $requestLocale),
                'date' => $formatter->dateShort($now),
                'time' => $formatter->time($now),
                'duration' => $formatter->duration(46120),
                'decimal' => $formatter->durationDecimal(46120),
                'money' => $formatter->money(2794.83, 'EUR'),
                'hour_24' => $service->is24Hour($locale),
                'rtl' => $service->isRightToLeft($locale),
            ];
        }

        return $data;
    }
}
