# DDMBundle

[![Packagist Version](https://img.shields.io/packagist/v/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![PHP Version Require](https://img.shields.io/packagist/php-v/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.4-673ab7?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/packagist/l/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Tests](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![PHP CS Fixer](https://img.shields.io/badge/php--cs--fixer-checked-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/phpstan-analyzed-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![Rector](https://img.shields.io/badge/rector-checked-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jbsnewmedia/ddm-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/jbsnewmedia/ddm-bundle)

**DDMBundle** (Data Definition Model) is a Symfony bundle for the VIS ecosystem that simplifies the definition and handling of data models for datatables and forms. It provides a structured way to centrally define fields, validations, and rendering logic.

## 🚀 Features

- **Centralized Data Definition** via DDM and DDMField
- **Automated Datatable Engine** for server-side processing (sorting, searching, pagination)
- **Flexible Form Handler** for AJAX-based form processing and validation
- **Attribute-based Field Configuration** for easy integration into entities
- **Extensive Validation** (Required, String, Unique, etc.)
- **Twig Integration** for easy rendering of forms and tables
- **Seamless Integration** into the VIS ecosystem

---

## ⚙️ Requirements

- PHP 8.2 or higher
- Symfony Framework 7.4 or higher
- Doctrine ORM

---

## 📦 Installation

Use [Composer](https://getcomposer.org/) to install the bundle:

```bash
composer require jbsnewmedia/ddm-bundle
```

---

## 🛠 Setup & Configuration

### 1. Define Fields

Fields are defined as services and can be configured with the `#[DDMFieldAttribute]` attribute to associate them with specific entities or contexts.

```php
use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;
use JBSNewMedia\DDMBundle\Service\DDMField;

#[DDMFieldAttribute(entity: 'User', order: 10)]
class UserNameField extends DDMField
{
    public function __construct()
    {
        $this->setIdentifier('username');
        $this->setName('Username');
        $this->setSortable(true);
        $this->setLivesearch(true);
    }
}
```

### 2. Create DDM Instance

Use the `DDMFactory` to create a DDM instance for an entity and a context:

```php
use JBSNewMedia\DDMBundle\Service\DDMFactory;

public function index(DDMFactory $ddmFactory)
{
    $ddm = $ddmFactory->create(User::class, 'admin_list');
    // ...
}
```

---

## 📋 Usage Examples

### Using Datatable in a Controller

The `DDMDatatableEngine` handles all the logic for providing the data:

```php
use JBSNewMedia\DDMBundle\Service\DDMDatatableEngine;
use JBSNewMedia\DDMBundle\Service\DDMFactory;
use Symfony\Component\HttpFoundation\Request;

public function list(Request $request, DDMFactory $ddmFactory, DDMDatatableEngine $engine)
{
    $ddm = $ddmFactory->create(User::class, 'list');
    
    if ($request->isXmlHttpRequest()) {
        return $engine->handleRequest($request, $ddm);
    }
    
    return $this->render('user/list.html.twig', [
        'ddm' => $ddm
    ]);
}
```

### Processing a Form

The `DDMDatatableFormHandler` automates loading, validating, and saving entities:

```php
use JBSNewMedia\DDMBundle\Service\DDMDatatableFormHandler;

public function edit(Request $request, User $user, DDMDatatableFormHandler $formHandler)
{
    $ddm = $this->ddmFactory->create(User::class, 'edit');
    
    $result = $formHandler->handle($request, $ddm, $user);
    
    if ($result instanceof Response) {
        return $result; // Returns the rendered form (HTML or JSON error)
    }
    
    if ($result['success']) {
        $this->entityManager->flush();
        return new JsonResponse(['success' => true]);
    }
}
```

---

## 🎨 Template Integration

### Datatable Rendering

```twig
<div id="user-datatable" 
     data-avalynx-datatable-url="{{ path('user_list_ajax') }}"
     data-avalynx-datatable-config="{{ ddm.datatableConfig|json_encode }}">
    <!-- The engine provides data suitable for AvalynX Datatable -->
</div>
```

---

## 📁 Architecture Overview

### Core Components

```
src/
├── Attribute/        # PHP Attributes for field configuration
├── DependencyInjection/ # Bundle configuration & extension
├── Service/
│   ├── DDM.php              # Central model of a data definition
│   ├── DDMFactory.php       # Factory for creating DDM instances
│   ├── DDMField.php         # Base class for all fields
│   ├── DDMDatatableEngine.php # Engine for datatable requests
│   └── DDMDatatableFormHandler.php # Handler for form logic
├── Validator/        # Standard validators (Required, String, etc.)
└── DDMBundle.php     # Bundle class
```

---

## 🧪 Developer Tools

The following commands are available for development:

```bash
# Tool installation
composer bin-ecs-install
composer bin-phpstan-install
composer bin-phpunit-install
composer bin-rector-install

# Code quality checks
composer bin-ecs           # PHP-CS-Fixer check
composer bin-phpstan       # Static analysis
composer bin-rector        # Code transformation (dry-run)
composer test              # PHPUnit tests

# Automatic fixes
composer bin-ecs-fix       # Fix coding standards
composer bin-rector-process # Apply code transformation

# CI Pipelines
composer ci                # Execute all checks
```

---

## 📜 License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

Developed by Jürgen Schwind and other contributors.

---

## 🤝 Contributing

Contributions are welcome! If you would like to contribute, please create a pull request or open an issue.

---

## 📫 Contact

If you have questions or problems, please open an issue in our [GitHub repository](https://github.com/jbsnewmedia/ddm-bundle).

---

*Data Definition Model. Modular. Efficient. VIS-Ready.*
