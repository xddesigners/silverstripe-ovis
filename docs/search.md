# Search fields
For full documentation on the OVIS search API you can contact [Internet Service Nederland BV](mailto:api-support@ovis.nl
). There you can request API access and receive full documentation.

This module uses a static search config you can taylor to your needs. Use the fields described below. _Don't include the `page` property._ 

| field | type | constraints | req |
|------ |------|-------------|-----|
| __userId__ | array | List of Ovis-userId's to be included in the search-query. | No |
| __presentationId__ | int | Look-up a single presentation. When this parameter is set: all other parameters are ignored. | No |
| __category__ | array | List of categories to be included in the search-query. Available options: _caravan, camper, mobilehome, tenttrailer, trailer_. Minimum 1 category. | Yes |
| __subCategory__ | array | List of subcategories to be included in the search-query. | No |
| __brand__ | array | List of brands to be included in the search-query. For available options see chapter: ​Categories, Brand and Models. |
| __modelTypeVersion__ | string | Max 20 characters. | No |
| __new__ | boolean | Show new object. Can be combined with _occasion_. | No |
| __occasion__ | boolean | Show occasions. Can be combined with _new_. | No |
| __rental__ | boolean | true -> Only show rentals. Cannot be combined with new and/or occasion | No |
| __priceFrom__ | float | Price starting of | No |
| __priceTo__ | float | Price limit to | No |
| __constructionYearFrom__ | int | Minimum: 1900. Maximum: currentYear + 2 | No |
| __constructionYearTo__ | int | Minimum: 1900. Maximum: currentYear + 2 | No |
| __maxWeightFrom__ | int |  | No |
| __maxWeightTo__ | int |  | No |
| __sleepingPlacesFrom__ | int |  | No |
| __sleepingPlacesTo__ | int |  | No |
| __transmission__ | string | Available options: _automatisch, handgeschakeld_ | No |
| __itemsPerPage__ | int | Number of items per page Minimum: 1 item Maximum 100 items | Yes |
| __order__ | array | List of ​order​-objects. Searchresult can be ordered by multiple-field. | No |
| __useCache__ | boolean | Use Query Cache or not Default: true | No |