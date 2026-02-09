<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DDMDatatableFormHandler
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator,
        protected Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return Response|array<string, mixed>
     */
    public function handle(Request $request, DDM $ddm, ?object $entity = null, bool $preload = false, string $template = '', array $options = []): Response|array
    {
        if ('' === $template) {
            $template = $ddm->getFormTemplate() ?? '@DDM/vis/form.html.twig';
        }

        if ($entity && !$preload) {
            foreach ($ddm->getFields() as $field) {
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $method = 'get'.((string) $field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $value = $entity->$method();
                    if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                        $field->setValue((string) $value);
                    }
                }
            }
        }

        if ($request->isMethod('POST')) {
            $invalid = [];
            $valid = [];
            $translationDomain = $options['translation_domain'] ?? null;

            foreach ($ddm->getFields() as $field) {
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $value = $request->request->get($field->getIdentifier());
                $error = null;

                if (!$value) {
                    $error = $this->translator->trans('ddm.fieldRequired', ['{field}' => $this->translator->trans($field->getName(), [], is_string($translationDomain) ? $translationDomain : null)], 'datatable');
                } else {
                    foreach ($field->getValidators() as $validator) {
                        if (!$validator->validate($value)) {
                            $error = $validator->getErrorMessage() ?? $this->translator->trans('ddm.fieldInvalid', ['{field}' => $this->translator->trans($field->getName(), [], is_string($translationDomain) ? $translationDomain : null)], 'datatable');
                            break;
                        }
                    }
                }

                if ($error) {
                    $invalid[$field->getIdentifier()] = $error;
                } else {
                    $valid[] = $field->getIdentifier();
                }
            }

            if (!empty($invalid)) {
                return new JsonResponse([
                    'success' => false,
                    'invalid' => $invalid,
                    'valid' => $valid,
                ]);
            }

            $isNew = false;
            if ($preload || !$entity) {
                $entityClass = $ddm->getEntityClass();
                $entity = new $entityClass();
                $isNew = true;
            }

            foreach ($ddm->getFields() as $field) {
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $method = 'set'.((string) $field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $entity->$method($request->request->get($field->getIdentifier()));
                }
            }

            return [
                'success' => true,
                'isNew' => $isNew,
                'entity' => $entity,
                'message' => $isNew ? $this->translator->trans('ddm.successCreate', [], 'datatable') : $this->translator->trans('ddm.successUpdate', [], 'datatable'),
            ];
        }

        $fields = [];
        foreach ($ddm->getFields() as $field) {
            if (!$field->isRenderInForm()) {
                continue;
            }
            $fields[] = $field;
        }

        $renderParams = array_merge($options, [
            'fields' => $fields,
            'ddm' => $ddm,
            'options' => $options,
        ]);

        $content = $this->twig->render($template, $renderParams);

        return new Response($content);
    }
}
