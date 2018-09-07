<?php

namespace XD\Ovis\Models;

use Image;

/**
 * Class PresentationMedia
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property boolean Default
 * @property int Sort
 * @method Presentation Presentation
 */
class PresentationMedia extends Image
{
    private static $table_name = 'PresentationMedia';

    private static $db = [
        'Default' => 'Boolean',
        'Sort' => 'Int'
    ];

    private static $default_sort = 'Sort ASC';

    private static $has_one = array(
        'Presentation' => 'XD\Ovis\Models\Presentation'
    );
}
