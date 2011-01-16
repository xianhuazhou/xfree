<?php
if (defined('DEBUG') && DEBUG == true) {
  ini_set('display_errors', true);
  error_reporting(E_ALL);
}

define('ROOT_DIR', realpath(__DIR__ . '/..'));

# F3 library
require ROOT_DIR . '/lib/classes/F.php';

# load helpers
require ROOT_DIR . '/lib/helper/RootHelper.php';

# pre-define some paths
require ROOT_DIR . '/config/paths.php';

# define autoload paths
v('AUTOLOAD', '../app/model|../lib/vendor/autoload|../lib/vendor/jpgraph|../app/controller|../lib/classes');

# load custom settings
load_configuration_for(array('settings', 'db'));

# load other libs such as jpgraph
# set_include_path(get_include_path() . PATH_SEPARATOR . v('path.lib.vendor') . '/jpgraph');

# default layout
layout('default');

# load routes
v('IMPORTS', v('path.app.controller') . '/');
require v('path.config') . '/routes.php';
