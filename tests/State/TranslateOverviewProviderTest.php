<?php

namespace ControleOnline\Common\Tests\State;

use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Entity\TranslateOverview;
use ControleOnline\Service\TranslateService;
use ControleOnline\State\CollectionSummaryResult;
use ControleOnline\State\TranslateOverviewProvider;
use PHPUnit\Framework\TestCase;

class TranslateOverviewProviderTest extends TestCase
{
    public function testProvideWrapsOverviewCollectionWithSummary(): void
    {
        $filters = [
            'people' => '2',
            'language.language' => 'pt-br',
        ];

        $translateService = $this->createMock(TranslateService::class);
        $translateService
            ->expects(self::once())
            ->method('buildOverview')
            ->with($filters)
            ->willReturn([
                'items' => [[
                    'rowId' => 'fallback-9',
                    'translateId' => null,
                    'fallbackId' => 9,
                    'language' => ['id' => 1, 'language' => 'pt-br'],
                    'people' => ['id' => 2, 'name' => 'Empresa 2'],
                    'mainCompany' => ['id' => 1, 'name' => 'Principal'],
                    'store' => 'translate',
                    'type' => 'title',
                    'key' => 'review_translations',
                    'translate' => 'Revisao de traducoes',
                    'revised' => false,
                    'pendingReview' => true,
                    'hasOverride' => false,
                    'source' => 'main_company',
                    'companyTranslate' => null,
                    'companyRevised' => null,
                    'mainTranslate' => 'Revisao de traducoes',
                    'mainRevised' => false,
                ]],
                'summary' => [
                    'total' => 1,
                    'pendingReview' => 1,
                    'reviewed' => 0,
                ],
            ]);

        $provider = new TranslateOverviewProvider($translateService);
        $result = $provider->provide(new GetCollection(), context: ['filters' => $filters]);

        self::assertInstanceOf(CollectionSummaryResult::class, $result);
        self::assertSame(['total' => 1, 'pendingReview' => 1, 'reviewed' => 0], $result->getSummary());

        $collection = $result->getCollection();
        self::assertIsArray($collection);
        self::assertCount(1, $collection);
        self::assertInstanceOf(TranslateOverview::class, $collection[0]);
        self::assertSame('fallback-9', $collection[0]->getRowId());
        self::assertTrue($collection[0]->isPendingReview());
        self::assertSame('Revisao de traducoes', $collection[0]->getTranslate());
    }
}
