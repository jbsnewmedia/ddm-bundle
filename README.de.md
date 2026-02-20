# DDMBundle

[![Packagist Version](https://img.shields.io/packagist/v/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Packagist Downloads](https://img.shields.io/packagist/dt/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![PHP Version Require](https://img.shields.io/packagist/php-v/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.4-673ab7?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/packagist/l/jbsnewmedia/ddm-bundle)](https://packagist.org/packages/jbsnewmedia/ddm-bundle)
[![Tests](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![PHP CS Fixer](https://img.shields.io/badge/php--cs--fixer-geprüft-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/phpstan-analysiert-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![Rector](https://img.shields.io/badge/rector-geprüft-brightgreen)](https://github.com/jbsnewmedia/ddm-bundle/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jbsnewmedia/ddm-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/jbsnewmedia/ddm-bundle)

**DDMBundle** (Data Definition Model) ist ein Symfony-Bundle für das VIS-Ökosystem, das die Definition und Handhabung von Datenmodellen für Datatables und Formulare vereinfacht. Es bietet eine strukturierte Möglichkeit, Felder, Validierungen und Render-Logik zentral zu definieren.

## 🚀 Features

- **Zentrale Datendefinition** via DDM und DDMField
- **Automatisierte Datatable-Engine** für serverseitige Verarbeitung (Sortierung, Suche, Pagination)
- **Erweiterte Suchfunktion** via DDMDatatableSearchHandler
- **Flexibler Form-Handler** für AJAX-basierte Formularverarbeitung und Validierung
- **Attribut-basierte Feldkonfiguration** für einfache Integration in Entities
- **Umfangreiche Validierung** (Required, String, Unique, Email, etc.)
- **Twig-Integration** für einfaches Rendering von Formularen und Tabellen
- **Nahtlose Integration** in das VIS-Ökosystem

---

## ⚙️ Anforderungen

- PHP 8.2 oder höher
- Symfony Framework 7.4 oder höher
- Doctrine ORM

---

## 📦 Installation

Verwende [Composer](https://getcomposer.org/), um das Bundle zu installieren:

```bash
composer require jbsnewmedia/ddm-bundle
```

---

## 🛠 Setup & Konfiguration

### 1. Felder definieren

Felder werden als Services definiert und können mit dem Attribut `#[DDMFieldAttribute]` konfiguriert werden, um sie bestimmten Entities oder Kontexten zuzuordnen. In der `__construct`-Methode oder via Initialisierung können Feld-Eigenschaften wie Bezeichner, Name und Verhalten festgelegt werden.

```php
use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;
use JBSNewMedia\DDMBundle\Service\DDMField;

#[DDMFieldAttribute(entity: 'User', order: 10)]
class UserNameField extends DDMField
{
    public function __construct()
    {
        $this->setIdentifier('username');
        $this->setName('Benutzername');
        $this->setSortable(true);
        $this->setLivesearch(true);
    }
}
```

### 2. DDM Instanz erstellen

Nutze die `DDMFactory`, um eine DDM-Instanz für eine Entity und einen Kontext zu erstellen:

```php
use JBSNewMedia\DDMBundle\Service\DDMFactory;

public function index(DDMFactory $ddmFactory)
{
    $ddm = $ddmFactory->create(User::class, 'admin_list');
    // ...
}
```

---

## 📋 Anwendungsbeispiele

### Datatable in einem Controller verwenden

Die `DDMDatatableEngine` übernimmt die gesamte Logik für die Bereitstellung der Daten:

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

### Formular verarbeiten

Der `DDMDatatableFormHandler` automatisiert das Laden, Validieren und Speichern von Entities. Er gibt entweder ein gerendertes Formular oder eine JSON-Antwort zurück.

```php
use JBSNewMedia\DDMBundle\Service\DDMDatatableFormHandler;
use Symfony\Component\HttpFoundation\Response;

public function edit(Request $request, User $user, DDMDatatableFormHandler $formHandler)
{
    $ddm = $this->ddmFactory->create(User::class, 'edit');
    
    $response = $formHandler->handle($request, $ddm, $user);
    
    return $response;
}
```

---

## 🎨 Template-Integration

### Datatable-Rendering

```twig
<div id="user-datatable" 
     data-avalynx-datatable-url="{{ path('user_list_ajax') }}"
     data-avalynx-datatable-config="{{ ddm.datatableConfig|json_encode }}">
    <!-- Die Engine liefert die Daten passend für AvalynX Datatable -->
</div>
```

---

## 📁 Architektur-Überblick

### Kern-Komponenten

```
src/
├── Attribute/           # PHP Attribute für die Feld-Konfiguration
├── Contract/            # Interfaces für Felder, Validatoren und Werte
├── DependencyInjection/ # Bundle-Konfiguration & Extension
├── Doctrine/            # Doctrine-spezifische Erweiterungen (z.B. CAST-Funktion)
├── Service/
│   ├── DDM.php              # Zentrales Modell einer Datendefinition
│   ├── DDMFactory.php       # Factory zum Erstellen von DDM-Instanzen
│   ├── DDMField.php         # Basisklasse für alle Felder
│   ├── DDMDatatableEngine.php # Engine für Datatable-Anfragen
│   ├── DDMDatatableFormHandler.php # Handler für Formular-Logik
│   └── DDMDatatableSearchHandler.php # Handler für die Datatable-Suche
├── Trait/               # Gemeinsam genutzte Funktionalitäten (z.B. Entity-Zugriff)
├── Validator/           # Validatoren (Required, String, Unique, Email, etc.)
├── Value/               # Wert-Objekte (String, Array, etc.)
└── DDMBundle.php        # Bundle-Klasse
```

---

## 🧪 Entwickler-Werkzeuge

Die folgenden Befehle stehen für die Entwicklung zur Verfügung:

```bash
# Installation der Werkzeuge
composer bin-ecs-install
composer bin-phpstan-install
composer bin-phpunit-install
composer bin-rector-install

# Code-Qualitätsprüfungen
composer bin-ecs           # PHP-CS-Fixer Prüfung
composer bin-phpstan       # Statische Analyse
composer bin-rector        # Code-Transformation (Dry-run)
composer test              # PHPUnit Tests

# Automatische Korrekturen
composer bin-ecs-fix       # Coding-Standards korrigieren
composer bin-rector-process # Code-Transformation anwenden

# CI-Pipelines
composer ci                # Alle Prüfungen ausführen
```

---

## 📜 Lizenz

Dieses Bundle ist unter der MIT-Lizenz lizenziert. Weitere Details findest Du in der Datei [LICENSE](LICENSE).

Entwickelt von Jürgen Schwind und weiteren Mitwirkenden.

---

## 🤝 Mitwirken

Beiträge sind willkommen! Wenn Du etwas beitragen möchtest, erstelle einen Pull-Request oder eröffne ein Issue.

---

## 📫 Kontakt

Wenn Du Fragen oder Probleme hast, eröffne bitte ein Issue in unserem [GitHub-Repository](https://github.com/jbsnewmedia/ddm-bundle).

---

*Data Definition Model. Modular. Effizient. VIS-Ready.*
