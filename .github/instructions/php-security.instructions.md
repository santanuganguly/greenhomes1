---
applyTo: '**/*.php'
description: 'Secure coding standards for PHP applications covering PDO parameterization, CSRF verification, and XSS prevention based on OWASP Top 10.'
---

# PHP Security & Backend Standards

When writing or modifying PHP backend files:

## 1. SQL Injection Prevention (OWASP A05)
- Always use PDO prepared statements with parameter binding:
  ```php
  $stmt = $db->prepare("SELECT id, name FROM quotes WHERE user_id = :user_id");
  $stmt->execute(['user_id' => $userId]);
  ```
- NEVER concatenate or interpolate raw variables into SQL queries.

## 2. Cross-Site Scripting (XSS) Prevention
- Wrap all output rendered in HTML templates with `htmlspecialchars()`:
  ```php
  <?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>
  ```
- Use `urlencode()` for URL query parameter values.

## 3. Cross-Site Request Forgery (CSRF) Protection
- All state-mutating requests (`POST`, `PUT`, `DELETE`) must verify a valid CSRF token:
  ```php
  require_once dirname(__DIR__, 2) . '/src/csrf.php';
  csrf_verify($_POST['_csrf'] ?? null);
  ```
- Include the token in every form:
  ```php
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>" />
  ```

## 4. Input Validation & Error Handling
- Validate all email inputs with `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- Never expose raw database exceptions or internal stack traces to the public interface.
- Always use typed declarations: `declare(strict_types=1);`.
