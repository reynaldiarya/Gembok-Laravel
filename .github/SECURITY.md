# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| latest  | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please report it by:

1. **Do NOT** create a public GitHub issue
2. Send an email to [rizkylab@gmail.com] with details
3. Include steps to reproduce the vulnerability
4. Allow up to 48 hours for initial response

## Security Measures

This project implements the following security measures:

### CI/CD Security Checks

- **SAST (Static Application Security Testing)**
  - PHPStan for PHP static analysis
  - Psalm for taint analysis and security vulnerabilities
  - Semgrep for pattern-based security scanning
  - CodeQL for advanced security analysis

- **Dependency Vulnerability Scanning**
  - Composer Audit for PHP dependencies
  - Local PHP Security Checker (Symfony)
  - NPM Audit for JavaScript dependencies
  - Dependabot for automated dependency updates

### Security Best Practices

- All security checks must pass before deployment
- Dependencies are automatically scanned weekly
- Pull requests require security review
- Production deployments require manual approval
