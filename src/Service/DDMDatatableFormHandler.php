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
        protected Environment $twig
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function handle(Request $request, DDM $ddm, object $entity = null, bool $preload = false, string $template = '', array $options = []): Response
    {
        if ($template === '') {
            $template = $ddm->getFormTemplate() ?? '@DDM/vis/form.html.twig';
        }

        if ($entity && !$preload) {
            foreach ($ddm->getFields() as $field) {
                if ($field->getIdentifier() === 'options') {
                    continue;
                }
                $method = 'get' . ucfirst($field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $field->setValue((string) $entity->$method());
                }
            }
        }

        if ($request->isMethod('POST')) {
            $invalid = [];
            $valid = [];
            $translationDomain = $options['translation_domain'] ?? null;

            foreach ($ddm->getFields() as $field) {
                if ($field->getIdentifier() === 'options') {
                    continue;
                }
                $value = $request->request->get($field->getIdentifier());
                $error = null;

                if (!$value) {
                    $error = $this->translator->trans('ddm.fieldRequired', ['{field}' => $this->translator->trans($field->getName(), [], $translationDomain)], 'datatable');
                } else {
                    foreach ($field->getValidators() as $validator) {
                        if (!$validator->validate($value)) {
                            $error = $validator->getErrorMessage() ?? $this->translator->trans('ddm.fieldInvalid', ['{field}' => $this->translator->trans($field->getName(), [], $translationDomain)], 'datatable');
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
                    'valid' => $valid
                ]);
            }

            $isNew = false;
            if ($preload || !$entity) {
                $entityClass = $ddm->getEntityClass();
                $entity = new $entityClass();
                $isNew = true;
            }

            foreach ($ddm->getFields() as $field) {
                if ($field->getIdentifier() === 'options') {
                    continue;
                }
                $method = 'set' . ucfirst($field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $entity->$method($request->request->get($field->getIdentifier()));
                }
            }

            if ($isNew) {
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'debug_msg' => $isNew ? $this->translator->trans('ddm.successCreate', [], 'datatable') : $this->translator->trans('ddm.successUpdate', [], 'datatable')
            ]);
        }

        $fields = [];
        foreach ($ddm->getFields() as $field) {
            if ($field->getIdentifier() === 'options') {
                continue;
            }
            $fields[] = $field;
        }

        $renderParams = array_merge($options, [
            'fields' => $fields,
            'ddm' => $ddm,
            'options' => $options,
        ]);

        return new Response($this->twig->render($template, $renderParams));
    }
}
