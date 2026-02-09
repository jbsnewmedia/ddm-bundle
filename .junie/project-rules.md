# Project Development Guide

This document provides essential information for AI and developers working on the DDMBundle project.

## Project Context
DDMBundle is a Symfony bundle that provides a Data Definition Model (DDM) engine. It is used to define and process data structures for datatables and forms in the VIS environment. Key features include a flexible field system, validators, and an engine for table rendering.

## Development Commands
All commands should be executed within the Docker container.

### Testing
- **Run PHPUnit tests:**
  `docker exec ddm-bundle-web-1 composer test`
- **Goal:** Maintain 100% code coverage.
- **Strict Rule:** `@codeCoverageIgnore` must never be used. All code paths must be tested.

### Code Quality & Static Analysis
- **PHPStan (Static Analysis):**
  `docker exec ddm-bundle-web-1 composer bin-phpstan`
- **Easy Coding Standard (ECS) - Fix issues:**
  `docker exec ddm-bundle-web-1 composer bin-ecs-fix`
- **Rector (Automated Refactoring):**
  `docker exec ddm-bundle-web-1 composer bin-rector-process`

## Code Style & Comments
- **Minimal Commenting**: All comments `//` that are not strictly necessary for Code Quality (e.g., PHPStan types) must be removed.
- **No Unnecessary Explanations**: Code should be self-explanatory. DocBlocks that only repeat method names or trivial logic are forbidden.
- **Cleanup Command**: If comments have been added, they can be cleaned up using `composer bin-ecs-fix` (if configured) or manually.

## Project Structure Highlights
- `.developer/`: Additional development documentation.
- `.junie/`: AI-specific configuration and documentation.
- `src/Attribute`: Contains PHP attributes for defining fields (`DDMFieldAttribute`).
- `src/DependencyInjection`: Configuration of bundle extensions (`DDMExtension`) and definition of the configuration (`Configuration`).
- `src/Service`: Core services such as `DDMFactory`, `DDMDatatableEngine`, and management of `DDMField` objects.
- `src/Validator`: Implements specific validation logic for DDM fields (e.g., `DDMStringValidator`, `DDMRequiredValidator`).
- `templates/`: Contains Twig templates for rendering datatables and form fields.
- `tests/`: Comprehensive test suite for core and plugin functionalities.


