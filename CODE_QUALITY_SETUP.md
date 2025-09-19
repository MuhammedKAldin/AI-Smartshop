# Code Quality Setup

This project is configured with comprehensive code quality tools to ensure high standards before submission.

## Tools Installed

### 1. PHPStan (Larastan) - Static Analysis
- **Level**: Maximum (Level max)
- **Configuration**: `phpstan.neon`
- **Purpose**: Static analysis to catch potential bugs and type issues
- **Extension**: Uses Larastan for Laravel-specific analysis

### 2. Laravel Pint - Code Styling
- **Configuration**: `pint.json`
- **Purpose**: Enforces Laravel coding standards and PSR-12 compliance

### 3. Blade Formatter - Template Formatting
- **Configuration**: `.bladeformatterrc.json`
- **Purpose**: Formats Blade templates for consistency

## Available Commands

### Run All Checks
```bash
composer test
```
This runs all quality checks in sequence:
1. PHPStan static analysis
2. Laravel Pint code style check
3. Blade Formatter template check

### Individual Commands

#### PHPStan
```bash
# Run static analysis
composer phpstan
# or directly
vendor\bin\phpstan analyze --xdebug
```

#### Laravel Pint
```bash
# Check code style (dry run)
composer pint
# or directly
vendor\bin\pint --test

# Fix code style issues
composer pint-fix
# or directly
vendor\bin\pint
```

#### Blade Formatter
```bash
# Check Blade template formatting
composer blade-format
# or directly
npx blade-formatter "./resources/**/*.blade.php" -c -d

# Fix Blade template formatting
composer blade-format-fix
# or directly
npx blade-formatter "./resources/**/*.blade.php" -w
```

## Configuration Files

### PHPStan (`phpstan.neon`)
- Maximum level (max) for strictest analysis
- Includes Laravel-specific rules via Larastan extension
- Analyzes: `app/`, `config/`, `database/`, `routes/`, `tests/`
- Excludes: `bootstrap/cache`, `storage`, `vendor`
- Uses Xdebug for better analysis

### Laravel Pint (`pint.json`)
- Laravel preset with additional strict rules
- Enforces PSR-12 and Laravel conventions
- Configures spacing, indentation, and code organization

### Blade Formatter (`.bladeformatterrc.json`)
- 4-space indentation
- 120 character line length
- Auto-wrap attributes
- Sort Tailwind CSS classes
- Remove multiple empty lines

## Pre-Submission Checklist

Before submitting code, ensure:

1. **All checks pass**: Run `composer test`
2. **No PHPStan errors**: Static analysis must be clean
3. **Code style compliant**: Laravel Pint must pass
4. **Blade templates formatted**: Blade Formatter must pass

## Quick Fix Commands

If you have formatting issues:

```bash
# Fix PHP code style
composer pint-fix

# Fix Blade template formatting
composer blade-format-fix
```

## Troubleshooting

### PHPStan Issues
- Review the error messages carefully
- Add type hints where missing
- Use `@phpstan-ignore-next-line` for false positives (sparingly)

### Pint Issues
- Run `composer pint-fix` to auto-fix most issues
- Review the changes before committing

### Blade Formatter Issues
- Run `composer blade-format-fix` to auto-fix formatting
- Check the configuration in `.bladeformatterrc.json`

## Windows Compatibility

All commands are configured for Windows compatibility:
- Uses `vendor\bin\` instead of `./vendor/bin/`
- All paths are Windows-compatible

## Integration with IDE

Consider configuring your IDE to:
- Run PHPStan on save
- Format code with Pint on save
- Format Blade templates on save

This ensures code quality is maintained during development.
