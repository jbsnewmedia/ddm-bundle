<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMDatatableSearchHandler;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

final class DDMDatatableSearchHandlerTest extends TestCase
{
    private function createDdmWithFields(EntityManagerInterface $em): DDM
    {
        if (!class_exists('SearchEntity')) { eval('class SearchEntity {}'); }

        $field1 = new class extends DDMField {};
        $field1->setIdentifier('name');
        $field1->setExtendsearch(true);
        $field1->setRenderSearch(true);

        $field2 = new class extends DDMField {};
        $field2->setIdentifier('hidden');
        $field2->setExtendsearch(false);
        $field2->setRenderSearch(true);

        $field3 = new class extends DDMField {};
        $field3->setIdentifier('not_render');
        $field3->setExtendsearch(true);
        $field3->setRenderSearch(false);

        $ddm = new DDM('SearchEntity', 'context', [], $em);
        $ddm->addField($field1);
        $ddm->addField($field2);
        $ddm->addField($field3);
        return $ddm;
    }

    public function testHandleGetRendersOnlyExtendAndRenderSearchFieldsAndPrefillsFromSession(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $twig = $this->createMock(Environment::class);
        $ddm = $this->createDdmWithFields($em);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $session->set('ddm_search_default', ['name' => 'Alice', 'ignored' => 'X']);

        $twig->method('render')->willReturnCallback(function (string $template, array $params): string {
            // Ensure only one field rendered and prefilled
            \PHPUnit\Framework\Assert::assertSame('@DDM/form/search.html.twig', $template);
            \PHPUnit\Framework\Assert::assertCount(1, $params['fields']);
            \PHPUnit\Framework\Assert::assertSame('Alice', $params['fields'][0]->getValueForm());
            \PHPUnit\Framework\Assert::assertTrue($params['is_search']);
            return 'html';
        });

        $handler = new DDMDatatableSearchHandler($twig);
        $response = $handler->handle($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('html', $response->getContent());
    }

    public function testHandlePostStoresFilteredSearchFields(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $twig = $this->createMock(Environment::class);
        $ddm = $this->createDdmWithFields($em);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], [
            'search_fields' => [
                'name' => 'Bob',
                'empty1' => '',
                'empty2' => [],
                'nullv' => null,
            ],
        ]);
        $request->setSession($session);
        $request->setMethod('POST');

        $handler = new DDMDatatableSearchHandler($twig);
        $response = $handler->handle($request, $ddm, '', ['id' => 'abc']);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(['name' => 'Bob'], $data['search_fields']);
        $this->assertSame(['name' => 'Bob'], $session->get('ddm_search_abc'));
    }

    public function testHandlePostResetClearsSession(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $twig = $this->createMock(Environment::class);
        $ddm = $this->createDdmWithFields($em);

        $session = new Session(new MockArraySessionStorage());
        $session->set('ddm_search_default', ['name' => 'X']);

        $request = new Request([], [
            'search_fields' => ['name' => 'Y'],
            '_reset' => '1',
        ]);
        $request->setSession($session);
        $request->setMethod('POST');

        $handler = new DDMDatatableSearchHandler($twig);
        $response = $handler->handle($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame([], $data['search_fields']);
        $this->assertSame([], $session->get('ddm_search_default', []));
    }
}
