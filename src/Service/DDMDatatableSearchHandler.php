<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DDMDatatableSearchHandler
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected Environment $twig
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function handle(Request $request, DDM $ddm, string $template = '', array $options = []): Response
    {
        if ($template === '') {
            $template = '@DDM/form/search.html.twig';
        }

        $session = $request->getSession();
        $sessionKey = 'ddm_search_' . ($options['id'] ?? 'default');

        if ($request->isMethod('POST')) {
            $searchFields = $request->request->all()['search_fields'] ?? [];

            // Clean empty values
            foreach ($searchFields as $key => $value) {
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    unset($searchFields[$key]);
                }
            }

            if ($request->request->has('_reset')) {
                $session->remove($sessionKey);
                return new JsonResponse([
                    'success' => true,
                    'search_fields' => []
                ]);
            }

            $session->set($sessionKey, $searchFields);

            return new JsonResponse([
                'success' => true,
                'search_fields' => $searchFields
            ]);
        }

        $searchData = $session->get($sessionKey, []);

        $fields = [];
        foreach ($ddm->getFields() as $field) {
            if (!$field->isExtendsearch()) {
                continue;
            }
            if (isset($searchData[$field->getIdentifier()])) {
                $field->setValueForm($searchData[$field->getIdentifier()]);
            }
            $fields[] = $field;
        }

        $renderParams = array_merge($options, [
            'fields' => $fields,
            'ddm' => $ddm,
            'options' => $options,
            'id' => $options['id'] ?? null,
            'is_search' => true
        ]);

        return new Response($this->twig->render($template, $renderParams));
    }
}
