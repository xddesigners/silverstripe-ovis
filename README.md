# OVIS module for Silverstripe
This module adds an import for [OVIS](https://www.ovis.nl/) presentations.
 
## Installation
Install the module trough composer and configure it with your search parameters, see the docs for all available search params.  
`composer require xddesigners/silverstripe-ovis`

### Basic config
```yaml
XD\Ovis\Ovis:
  api_key: 'YOUR_API_KEY'
  search:
    category:
      - mobilehome
      - tenttrailer
      - caravan
      - camper

XD\Ovis\Models\Order:
  email_from: 'YOUR_FROM_ADDRESS' # defaults to Email.admin_email
  email_to: 'YOUR_TO_ADDRESS' # defaults to Email.admin_email
```

### Set up import script
You can run the import script manually trough the dev/tasks interface or set up up to run as a cron task. 
`http://example.com/dev/tasks/XD-Ovis-Tasks-Import` or `sake dev/tasks/XD-Ovis-Tasks-Import`