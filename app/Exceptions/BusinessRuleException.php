<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that is well-formed but violates a business rule (overselling,
 * overpaying, receiving more than ordered...). Rendered as a validation-style
 * error in bootstrap/app.php, and always thrown inside a DB transaction so
 * nothing partial is persisted.
 */
class BusinessRuleException extends RuntimeException {}
