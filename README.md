# Convector for OfflineQuiz

`Convector_Plugin` provides the `local_convector` Moodle plugin for Moodle 4.5+.

Convector analyzes OfflineQuiz groups, computes the page gap between them, then generates a normalized ZIP archive containing:

- merged questionnaires
- answer sheets
- correction forms

## Requirements

- Moodle 4.5+
- `mod_offlinequiz` for Moodle 4.5 (`MOODLE_405_STABLE`)
- PHP 8.1+

## Architecture

The plugin now follows the same modular style as `Anonymous_Plugin` and `Export_Plugin`:

- thin entrypoints in `temporal.php`, `view.php` and `lib.php`
- orchestration through `classes/manager.php`
- dependency wiring in `classes/manager_factory.php`
- HTTP/page concerns isolated in `classes/controller/`
- Moodle navigation glue isolated in `classes/integration/`
- business services isolated in `classes/service/`
- PDF generation split between generators and dedicated helpers/services

## Quality Tooling

The repository includes:

- `composer.json` for PHPCS dependencies
- `phpcs.xml.dist` using Moodle coding standards
- `tools/quality_guard.php` for local architectural guardrails
- `phpunit.xml` and PHPUnit tests in `tests/`
- GitHub Actions CI covering quality guard, PHPCS, lint and Moodle PHPUnit

## Installation

1. Copy the plugin to `local/convector`
2. Install or update the site via Moodle notifications
3. Ensure `mod_offlinequiz` is installed on the same Moodle instance

## Usage

1. Open an OfflineQuiz activity
2. Follow the Convector entry added by the plugin navigation integration
3. Review the analysis page
4. Generate the normalized ZIP archive when required

## Development Notes

- Temporary generation artifacts are isolated per request under Moodle temp storage
- ZIP download is delegated to Moodle temp-file APIs
- Generation requires the dedicated capability `local/convector:generate`

## License

GNU GPL v3 or later
