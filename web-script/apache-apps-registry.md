# Apache Applications Registry
# This file tracks all configured applications for reference

# Format: DATE | APP_NAME | PHP_VERSION | TYPE | URL | STATUS
# DATE: Configuration date
# APP_NAME: Application directory name
# PHP_VERSION: 7.4 or 8.4
# TYPE: codeigniter, laravel, wordpress, basic
# URL: Access URL
# STATUS: active, inactive, removed

## Currently Configured Applications

### 2025-10-27 | posplus | 7.4 | codeigniter | http://php74.localhost:8080/posplus/ | active
# ePOSPlus API System
# Path: /Volumes/Work/Work-Angular/Work13/apps/ePOSPlus/Apis
# Symlink: /opt/homebrew/var/www/php74/posplus
# Notes: CodeIgniter 3.x API system with database integration

## Configuration History

### 2025-10-27 Initial Setup
- Configured Apache 2.4.65 with Homebrew
- Set up PHP 7.4 and PHP 8.4 with PHP-FPM
- Created virtual hosts for php74.localhost and php84.localhost
- Established symbolic link methodology for application deployment

## Quick Commands

### Add New Application
```bash
# Using the automated script
./add-apache-app.sh -n app-name -p /path/to/app -v 7.4 -t codeigniter

# Manual method
sudo ln -s "/path/to/app" /opt/homebrew/var/www/php74/app-name
# Then edit /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf
```

### Remove Application
```bash
# Remove symbolic link
sudo rm /opt/homebrew/var/www/phpXX/app-name

# Remove configuration from httpd-vhosts.conf (manual)
# Restart Apache: sudo brew services restart httpd
```

### List All Applications
```bash
# List symbolic links
ls -la /opt/homebrew/var/www/php74/
ls -la /opt/homebrew/var/www/php84/

# Check running applications
curl -I http://php74.localhost:8080/
curl -I http://php84.localhost:8080/
```

## Maintenance Notes

### Regular Tasks
- Check Apache and PHP-FPM service status monthly
- Review application logs for errors
- Update PHP versions as needed
- Backup Apache configuration before major changes

### Backup Commands
```bash
# Backup Apache configuration
sudo cp /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf \
       /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf.backup-$(date +%Y%m%d)

# Backup hosts file
sudo cp /etc/hosts /etc/hosts.backup-$(date +%Y%m%d)
```

### Service Management
```bash
# Apache
brew services start httpd
brew services stop httpd  
brew services restart httpd

# PHP-FPM
brew services start php@7.4
brew services start php
brew services restart php@7.4
brew services restart php
```

## File Locations Reference

- **Apache Config**: `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`
- **Document Root**: `/opt/homebrew/var/www/`
- **PHP 7.4 Apps**: `/opt/homebrew/var/www/php74/`
- **PHP 8.4 Apps**: `/opt/homebrew/var/www/php84/`
- **Apache Logs**: `/opt/homebrew/var/log/httpd/`
- **Hosts File**: `/etc/hosts`

## Application Templates Available

1. **CodeIgniter 3.x**: Full URL rewriting, security restrictions
2. **Laravel**: Modern PHP framework support
3. **WordPress**: CMS with security considerations
4. **Basic PHP**: Simple PHP applications

---

*Last Updated: 2025-10-27*
*Registry Version: 1.0*
