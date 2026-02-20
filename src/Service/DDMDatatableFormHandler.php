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
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $method = 'get' . ucfirst($field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $field->setValueForm($field->prepareValue($entity->$method()));
                }
            }
        }

        if ($request->isMethod('POST')) {
            $invalid = [];
            $valid = [];
            $translationDomain = $options['translation_domain'] ?? null;

            $ddm->setEntity($entity);

            foreach ($ddm->getFields() as $field) {
                if (!$field->isRenderInForm()) {
                    continue;
                }

                $value = $request->request->all()[$field->getIdentifier()] ?? null;
                $error = null;

                if (!$field->validate($value)) {
                    $fieldError = $field->getError();
                    $error = $this->translator->trans($fieldError['message'], $fieldError['parameters'] ?? [], $fieldError['domain']);
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
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $method = 'set' . ucfirst($field->getIdentifier());
                if (method_exists($entity, $method)) {
                    $value = $request->request->all()[$field->getIdentifier()] ?? null;
                    $entity->$method($field->finalizeValue($value));
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
            if (!$field->isRenderInForm()) {
                continue;
            }
            $fields[] = $field;
        }

        $renderParams = array_merge($options, [
            'fields' => $fields,
            'ddm' => $ddm,
            'options' => $options,
            'id' => $options['id'] ?? null,
        ]);

        return new Response($this->twig->render($template, $renderParams));
    }
}
