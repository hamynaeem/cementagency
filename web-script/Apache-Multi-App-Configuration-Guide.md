# Apache PHP Multi-Version Configuration Guide
## History, Reference & Method for Adding New Applications

### Table of Contents
1. [Current Configuration Overview](#current-configuration-overview)
2. [Configuration History](#configuration-history)
3. [Step-by-Step Method for Adding New Apps](#step-by-step-method-for-adding-new-apps)
4. [Configuration Templates](#configuration-templates)
5. [Troubleshooting Guide](#troubleshooting-guide)
6. [Best Practices](#best-practices)

---

## Current Configuration Overview

### System Information
- **OS**: macOS
- **Apache**: Homebrew Apache 2.4.65
- **Apache Config**: `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`
- **Document Root**: `/opt/homebrew/var/www/`
- **Port**: 8080

### Active Virtual Hosts
| Domain | PHP Version | Document Root | Status |
|--------|-------------|---------------|---------|
| localhost | PHP 8.4 | `/opt/homebrew/var/www` | ✅ Active |
| php74.localhost | PHP 7.4 | `/opt/homebrew/var/www/php74` | ✅ Active |
| php84.localhost | PHP 8.4 | `/opt/homebrew/var/www/php84` | ✅ Active |

### PHP-FPM Configuration
| PHP Version | Port | Status | Service Command |
|-------------|------|--------|-----------------|
| PHP 7.4 | 9074 | ✅ Running | `brew services start php@7.4` |
| PHP 8.4 | 9000 | ✅ Running | `brew services start php` |

### Current Applications
| Application | URL | Path | Type |
|-------------|-----|------|------|
| ePOSPlus APIs | http://php74.localhost:8080/posplus | `/Volumes/Work/Work-Angular/Work13/apps/ePOSPlus/Apis` | CodeIgniter 3.x |

---

## Configuration History

### 2025-10-27: ePOSPlus API Configuration

#### Initial Attempt (Failed)
- **Method**: Apache Alias directive
- **Issue**: Alias directive not working properly
- **Error**: 404 Not Found errors
- **Config Used**:
  ```apache
  Alias /posplus "/Volumes/Work/Work-Angular/Work13/apps/ePOSPlus/Apis"
  ```

#### Successful Solution
- **Method**: Symbolic Link + Directory Configuration
- **Implementation**:
  1. Created symbolic link: `sudo ln -s "/Volumes/Work/Work-Angular/Work13/apps/ePOSPlus/Apis" /opt/homebrew/var/www/php74/posplus`
  2. Updated virtual host configuration
  3. Configured CodeIgniter base_url and .htaccess

#### Lessons Learned
- Symbolic links are more reliable than Alias directives in this setup
- Always test configuration syntax with `httpd -t`
- PHP-FPM requires specific port configuration
- CodeIgniter requires specific URL rewriting rules

---

## Step-by-Step Method for Adding New Apps

### Prerequisites Checklist
- [ ] Apache running on port 8080
- [ ] Required PHP version installed and PHP-FPM running
- [ ] Domain entry in `/etc/hosts` (if needed)
- [ ] Source code accessible on the system

### Method 1: Adding App to Existing PHP Version Domain

#### Step 1: Create Symbolic Link
```bash
# For PHP 7.4 apps
sudo ln -s "/path/to/your/app" /opt/homebrew/var/www/php74/your-app-name

# For PHP 8.4 apps  
sudo ln -s "/path/to/your/app" /opt/homebrew/var/www/php84/your-app-name
```

#### Step 2: Add Directory Configuration
Edit `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf` and add to the appropriate virtual host:

```apache
# For CodeIgniter Apps
<Directory "/opt/homebrew/var/www/php74/your-app-name">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
    
    # Enable rewrite engine for CodeIgniter
    RewriteEngine On
    RewriteBase /your-app-name/
    
    # CodeIgniter specific rules
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond $1 !^(index\.php|assets|install|update)
    RewriteRule ^(.*)$ index.php/$1 [NC,L,QSA]
</Directory>

# For Laravel Apps
<Directory "/opt/homebrew/var/www/php74/your-app-name">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
    
    # Laravel specific rules
    RewriteEngine On
    RewriteBase /your-app-name/
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</Directory>

# For WordPress Apps
<Directory "/opt/homebrew/var/www/php74/your-app-name">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
</Directory>

# For Basic PHP Apps
<Directory "/opt/homebrew/var/www/php74/your-app-name">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php index.html
</Directory>
```

#### Step 3: Configure Application
Update application configuration files:

**For CodeIgniter:**
```php
// application/config/config.php
$config['base_url'] = 'http://php74.localhost:8080/your-app-name/';
$config['index_page'] = '';

// .htaccess
RewriteBase /your-app-name/
```

**For Laravel:**
```php
// .env
APP_URL=http://php74.localhost:8080/your-app-name
```

#### Step 4: Test and Restart
```bash
# Test configuration
httpd -t

# Restart Apache
sudo brew services restart httpd

# Test application
curl http://php74.localhost:8080/your-app-name/
```

### Method 2: Adding New Domain for New App

#### Step 1: Add Host Entry
```bash
# Add to /etc/hosts
echo "127.0.0.1    your-app.localhost" | sudo tee -a /etc/hosts
```

#### Step 2: Create Document Root
```bash
sudo mkdir -p /opt/homebrew/var/www/your-app
sudo ln -s "/path/to/your/app" /opt/homebrew/var/www/your-app/
```

#### Step 3: Add Virtual Host
Add to `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`:

```apache
# Your New App
<VirtualHost *:8080>
    ServerName your-app.localhost
    DocumentRoot "/opt/homebrew/var/www/your-app"
    DirectoryIndex index.php index.html
    
    <Directory "/opt/homebrew/var/www/your-app">
        AllowOverride All
        Require all granted
        Options +FollowSymLinks
    </Directory>
    
    # PHP Version (7.4 or 8.4)
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://127.0.0.1:9074"  # For PHP 7.4
        # SetHandler "proxy:fcgi://127.0.0.1:9000"  # For PHP 8.4
    </FilesMatch>
    
    ErrorLog "/opt/homebrew/var/log/httpd/your-app_error.log"
    CustomLog "/opt/homebrew/var/log/httpd/your-app_access.log" common
</VirtualHost>
```

---

## Configuration Templates

### Template 1: CodeIgniter Application
```apache
# CodeIgniter App on PHP 7.4
<Directory "/opt/homebrew/var/www/php74/APP_NAME">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
    
    RewriteEngine On
    RewriteBase /APP_NAME/
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond $1 !^(index\.php|assets|install|update)
    RewriteRule ^(.*)$ index.php/$1 [NC,L,QSA]
</Directory>

# Security for CodeIgniter
<Directory "/opt/homebrew/var/www/php74/APP_NAME/system">
    Require all denied
</Directory>
<Directory "/opt/homebrew/var/www/php74/APP_NAME/application">
    Require all denied
</Directory>
```

### Template 2: Laravel Application
```apache
# Laravel App on PHP 8.4
<Directory "/opt/homebrew/var/www/php84/APP_NAME">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
    
    RewriteEngine On
    RewriteBase /APP_NAME/
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L]
</Directory>
```

### Template 3: WordPress Application
```apache
# WordPress App on PHP 8.4
<Directory "/opt/homebrew/var/www/php84/APP_NAME">
    AllowOverride All
    Require all granted
    Options +FollowSymLinks -Indexes
    DirectoryIndex index.php
    
    # WordPress specific security
    <Files "wp-config.php">
        Require all denied
    </Files>
</Directory>
```

### Template 4: New Virtual Host
```apache
# New Virtual Host Template
<VirtualHost *:8080>
    ServerName APP_DOMAIN.localhost
    DocumentRoot "/opt/homebrew/var/www/APP_NAME"
    DirectoryIndex index.php index.html
    
    <Directory "/opt/homebrew/var/www/APP_NAME">
        AllowOverride All
        Require all granted
        Options +FollowSymLinks
    </Directory>
    
    # PHP Version Selection
    <FilesMatch \.php$>
        SetHandler "proxy:fcgi://127.0.0.1:PHP_FPM_PORT"
    </FilesMatch>
    
    ErrorLog "/opt/homebrew/var/log/httpd/APP_NAME_error.log"
    CustomLog "/opt/homebrew/var/log/httpd/APP_NAME_access.log" common
</VirtualHost>
```

---

## Troubleshooting Guide

### Common Issues and Solutions

#### 1. 404 Not Found
**Symptoms**: Application not accessible, returns 404
**Causes**:
- Symbolic link not created properly
- Incorrect path in configuration
- Directory permissions

**Solutions**:
```bash
# Check symbolic link
ls -la /opt/homebrew/var/www/php74/

# Recreate symbolic link
sudo rm /opt/homebrew/var/www/php74/app-name
sudo ln -s "/correct/path/to/app" /opt/homebrew/var/www/php74/app-name

# Check permissions
sudo chmod -R 755 /path/to/your/app
```

#### 2. Apache Configuration Errors
**Symptoms**: Apache won't start, syntax errors
**Solutions**:
```bash
# Test configuration
httpd -t

# Check for common syntax errors:
# - Missing quotes in paths
# - Incorrect Options syntax (use +/- prefixes)
# - Unclosed directory blocks
```

#### 3. PHP Not Executing
**Symptoms**: PHP files download instead of executing
**Causes**:
- PHP-FPM not running
- Incorrect PHP handler configuration
- Wrong PHP-FPM port

**Solutions**:
```bash
# Check PHP-FPM status
brew services list | grep php

# Start PHP-FPM if needed
brew services start php@7.4
brew services start php

# Check ports
lsof -i :9074  # PHP 7.4
lsof -i :9000  # PHP 8.4
```

#### 4. URL Rewriting Not Working
**Symptoms**: Only homepage works, other URLs return 404
**Causes**:
- mod_rewrite not enabled
- Incorrect RewriteBase
- Missing .htaccess rules

**Solutions**:
```bash
# Check if mod_rewrite is loaded
httpd -M | grep rewrite

# Verify RewriteBase matches URL structure
# For /app-name/ URLs, use: RewriteBase /app-name/
```

### Diagnostic Commands

```bash
# Apache status and configuration
brew services list | grep httpd
httpd -S
httpd -t

# PHP-FPM status
brew services list | grep php
lsof -i :9074
lsof -i :9000

# Virtual host testing
curl -I http://domain.localhost:8080/app/

# Log monitoring
tail -f /opt/homebrew/var/log/httpd/error_log
tail -f /opt/homebrew/var/log/httpd/php74_error.log
```

---

## Best Practices

### 1. File Organization
```
/opt/homebrew/var/www/
├── php74/                    # PHP 7.4 applications
│   ├── legacy-app1/         # Symbolic link
│   ├── legacy-app2/         # Symbolic link
│   └── posplus/             # ePOSPlus APIs
├── php84/                   # PHP 8.4 applications
│   ├── modern-app1/         # Symbolic link
│   └── modern-app2/         # Symbolic link
└── index.html               # Default page
```

### 2. Naming Conventions
- **Domains**: `app-name.localhost` or `phpXX.localhost/app-name`
- **Directories**: Use lowercase, hyphens for spaces
- **Symbolic links**: Match the URL path structure

### 3. Security Considerations
- Always deny access to sensitive directories (system/, application/, etc.)
- Use HTTPS in production
- Keep PHP versions updated
- Implement proper authentication for APIs

### 4. Documentation
- Document each application's requirements
- Keep track of database configurations
- Note any custom Apache modules needed
- Record application-specific configurations

### 5. Backup Strategy
```bash
# Backup current configuration
sudo cp /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf \
        /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf.backup-$(date +%Y%m%d)

# Backup hosts file
sudo cp /etc/hosts /etc/hosts.backup-$(date +%Y%m%d)
```

---

## Quick Reference Commands

### Adding New App (Summary)
```bash
# 1. Create symbolic link
sudo ln -s "/path/to/app" /opt/homebrew/var/www/php74/app-name

# 2. Add directory config to httpd-vhosts.conf
# (Use templates above)

# 3. Test and restart
httpd -t
sudo brew services restart httpd

# 4. Test application
curl http://php74.localhost:8080/app-name/
```

### Useful File Paths
- **Apache Config**: `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`
- **Hosts File**: `/etc/hosts`
- **Apache Logs**: `/opt/homebrew/var/log/httpd/`
- **Document Root**: `/opt/homebrew/var/www/`

---

*Last Updated: 2025-10-27*
*Configuration Method Version: 1.0*
