<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DDMDatatableSearchHandler
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * Handles extended search form rendering (GET) and search session persistence (POST).
     *
     * @param array<string, mixed> $options
     */
    public function handle(Request $request, DDM $ddm, string $template = '', array $options = []): Response
    {
        if ('' === $template) {
            $template = '@DDM/form/search.html.twig';
        }

        $session = $request->getSession();
        $sessionId = isset($options['id']) && (is_string($options['id']) || is_int($options['id']))
            ? (string) $options['id']
            : 'default';
        $sessionKey = 'ddm_search_'.$sessionId;

        if ($request->isMethod('POST')) {
            /** @var array<string, mixed> $searchFields */
            $searchFields = $request->request->all()['search_fields'] ?? [];

            // Remove empty values
            foreach ($searchFields as $key => $value) {
                if (null === $value || '' === $value || (is_array($value) && [] === $value)) {
                    unset($searchFields[$key]);
                }
            }

            if ($request->request->has('_reset')) {
                $session->remove($sessionKey);

                return new JsonResponse([
                    'success' => true,
                    'search_fields' => [],
                ]);
            }

            $session->set($sessionKey, $searchFields);

            return new JsonResponse([
                'success' => true,
                'search_fields' => $searchFields,
            ]);
        }

        /** @var array<string, mixed> $searchData */
        $searchData = $session->get($sessionKey, []);

        $fields = [];
        foreach ($ddm->getFields() as $field) {
            if (!$field->isExtendsearch() || !$field->isRenderSearch()) {
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
            'is_search' => true,
        ]);

        return new Response($this->twig->render($template, $renderParams));
    }
}
