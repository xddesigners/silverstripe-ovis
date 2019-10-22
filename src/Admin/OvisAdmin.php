<?php

namespace XD\Ovis\Admin;

use SilverStripe\Admin\ModelAdmin;

/**
 * Class OvisAdmin
 *
 * @author Bram de Leeuw
 * @package caravanextra
 *
 * @property array managed_models  An array of classnames to manage.
 * @property string url_segment     The url section of this admin section.
 * @property string menu_title      The menu title for this admin section.
 * @property string menu_icon       The menu icon for this admin section.
 */
class OvisAdmin extends ModelAdmin
{
    private static $managed_models = [
        'XD\Ovis\Models\Order',
        'XD\Ovis\Models\Presentation'
    ];

    private static $url_segment = 'ovis';

    private static $menu_title = 'OVIS';

    private static $menu_icon = '/ovis/images/ovis.png';
}
