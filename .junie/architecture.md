# DDM Bundle – Architecture

## Overview

The **Data Definition Model (DDM) Bundle** is a Symfony bundle that provides generic
**datatable + form handling** for entity CRUD operations. It combines:

- Field configuration via PHP Attributes (`DDMFieldAttribute`) + Tagged Services
- Doctrine QueryBuilder-based datatable with search/sort/pagination
- Form rendering + validation via a custom validator chain
- Twig templates with a JavaScript layer (AvalynxDataTable / AvalynxModal)

## Directory Structure

```text
ddm-bundle/
├── config/
│   └── services.yaml          # Auto-wiring + tagged iterator for DDMField
├── src/
│   ├── Attribute/
│   │   └── DDMFieldAttribute.php   # PHP 8 Attribute: binds a DDMField to an entity/context
│   ├── DependencyInjection/
│   │   ├── Configuration.php       # Bundle config tree (currently empty)
│   │   └── DDMExtension.php        # Registers Twig paths + Doctrine CAST function
│   ├── Doctrine/ORM/Query/AST/Functions/
│   │   └── Cast.php                # Custom DQL CAST() function
│   ├── Service/
│   │   ├── DDM.php                 # Aggregate: holds entity class, context, fields, routes
│   │   ├── DDMFactory.php          # Creates DDM instances (injects all tagged DDMFields)
│   │   ├── DDMField.php            # Abstract base for all field types
│   │   ├── DDMDatatableEngine.php  # Handles JSON datatable requests (search/sort/page)
│   │   ├── DDMDatatableFormHandler.php  # Handles form GET (render) + POST (save)
│   │   └── DDMDatatableSearchHandler.php # Handles extended search form + session storage
│   ├── Validator/
│   │   ├── DDMValidator.php        # Abstract base validator
│   │   ├── DDMEmailValidator.php
│   │   ├── DDMRequiredValidator.php
│   │   ├── DDMStringValidator.php
│   │   └── DDMUniqueValidator.php  # DB-backed uniqueness check via EntityManager
│   └── Value/
│       ├── DDMValue.php            # Abstract: type + getValue/setValue/__toString
│       ├── DDMStringValue.php      # Holds a nullable string
│       └── DDMArrayValue.php       # Holds an array, serializes to comma-separated string
├── templates/
│   ├── datatable/
│   │   ├── default.html.twig       # Full datatable card + JS (AvalynxDataTable)
│   │   └── header.html.twig
│   ├── fields/
│   │   ├── text.html.twig          # Default Bootstrap text input
│   │   ├── hidden.html.twig
│   │   └── roles.html.twig
│   ├── form/
│   │   ├── default.html.twig       # Form with AvalynxForm JS (iframe postMessage)
│   │   ├── header.html.twig
│   │   └── search.html.twig
│   └── vis/
│       ├── datatable.html.twig     # vis-bundle integration template
│       └── form.html.twig
└── translations/
    ├── datatable.{de,en}.yaml
    ├── ddm_validator_email.{de,en}.yaml
    ├── ddm_validator_required.{de,en}.yaml
    ├── ddm_validator_string.{de,en}.yaml
    └── ddm_validator_unique.{de,en}.yaml
```

## Core Data Flow

### Datatable (GET/POST JSON)

```
Controller
  └── DDMFactory::create($entityClass, $context)
        └── DDM (aggregates matched DDMFields)
              └── DDMDatatableEngine::handleRequest(Request, DDM, ?QueryBuilder)
                    ├── Builds head columns from field metadata
                    ├── Applies global search (LIKE via field::getSearchExpression)
                    ├── Applies extended search (per-field)
                    ├── Paginates + sorts
                    └── Returns JsonResponse { head, data, count, sorting, search }
```

### Form (render + save)

```
Controller
  └── DDMDatatableFormHandler::handle(Request, DDM, ?entity, preload, template, options)
        ├── GET: populate fields from entity → render Twig template
        └── POST:
              ├── Validate all fields (DDMValidator chain, fail-fast per field)
              ├── On error → JsonResponse { success: false, invalid: {...}, valid: [...] }
              └── On success → persist/flush entity → JsonResponse { success: true, message }
```

### Extended Search

```
Controller
  └── DDMDatatableSearchHandler::handle(Request, DDM, template, options)
        ├── GET: load search data from session → render search form
        └── POST:
              ├── _reset: clear session key → JsonResponse { success: true, search_fields: [] }
              └── save search_fields to session → JsonResponse { success: true, search_fields }
```

## Key Design Decisions

### Tagged Services for Fields

All `DDMField` subclasses are auto-tagged as `ddm.field` via `_instanceof` in `services.yaml`.
`DDMFactory` receives all tagged fields via `!tagged_iterator ddm.field`.

```yaml
_instanceof:
    JBSNewMedia\DDMBundle\Service\DDMField:
        tags: ['ddm.field']

JBSNewMedia\DDMBundle\Service\DDMFactory:
    arguments:
        $fields: !tagged_iterator ddm.field
```

### Field Matching via Attribute

`DDMFieldAttribute` binds a field class to an entity + context. Matching logic in
`DDM::loadFields()` checks:
- `entity` matches full class name OR short name (case-insensitive)
- OR `identifier` matches the context string

### Value Handlers

Each `DDMField` has a `DDMValue` (default: `DDMStringValue`). The value handler:
- Stores the current field value
- Serializes to string via `__toString()` (used in datatable rendering)
- Provides typed `getValue()` return

### Validator Chain

Validators are sorted by `priority` (descending). Validation stops at the **first** error.
Error messages are translation keys; the domain is derived from the validator alias:
`ddm_validator_{alias}`.

## JavaScript Integration

The datatable template (`datatable/default.html.twig`) uses:
- **AvalynxDataTable** – fetches JSON from the API endpoint
- **AvalynxModal** – wraps forms in iframes for add/edit/search
- **AvalynxForm** – submits forms via fetch + postMessage to parent

Communication between iframe (form) and parent (datatable) uses `window.postMessage`:
- `submit-form` → triggers form submission
- `{ type: 'form-success', response }` → closes modal, refreshes datatable
- `{ type: 'form-error', response }` → re-enables save button
- `{ type: 'reset-search' }` → resets extended search

## Requirements

- PHP ≥ 8.3
- Symfony Framework Bundle 7.4.*
- Symfony Translation Contracts ^3.0
- Doctrine ORM (optional, auto-detected in `DDMExtension::prepend()`)
- Twig (optional, auto-detected in `DDMExtension::prepend()`)
