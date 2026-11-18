---
description: 'Verify syntax and prepare FTP upload commands for Hostinger deployment.'
agent: agent
argument-hint: '[file-path]'
---

# Deploy Check & Hostinger FTP Protocol

## Mission
Safely verify, lint, and prepare upload commands for deploying modified files to the Hostinger live environment.

## Protocol
1. **Lint Syntax**:
   - Run PHP linter: `C:\xampp\php\php.exe -l <file>`
   - Verify there are no syntax errors or unclosed tags.
2. **Review Environment**:
   - Ensure no hardcoded secrets or passwords are added to Git.
   - For public pages, verify database failovers are gracefully handled.
3. **Generate FTP Upload Command**:
   - For CRM files:
     ```bash
     curl.exe -T <relative/path/to/file.php> ftp://ftp.thegreen.homes/<relative/path/to/file.php> --user u495244335.u495244335crm:GreenHomesCRM@2026!
     ```
