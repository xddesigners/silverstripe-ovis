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
```