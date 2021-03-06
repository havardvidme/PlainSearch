<?php
/**
 * PlainSearch
 *
 * Copyright 2015 by Håvard Vidme <havard.vidme@gmail.com>
 *
 * PlainSearch is free software; you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * PlainSearch is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * PlainSearch; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package plainsearch
 */
/**
 * PlainSearch transport package build script
 *
 * @package plainsearch
 * @subpackage build
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/* set package defines */
define('PKG_ABBR','plainsearch');
define('PKG_NAME','Plainsearch');
define('PKG_VERSION','1.0');
define('PKG_RELEASE','pl');

/* override with your own defines here (see build.config.sample.php) */
require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once dirname(__FILE__). '/includes/functions.php';

$modx= new modX();
$root = dirname(dirname(__FILE__)).'/';
$assets = MODX_ASSETS_PATH.'components/'.PKG_ABBR.'/';
$sources= array (
    'root' => $root,
    'build' => $root.'_build/',
    'resolvers' => $root.'_build/resolvers/',
    'data' => $root.'_build/data/',
    'properties' => $root.'_build/properties/',
    'source_core' => $root.'core/components/'.PKG_ABBR,
    'source_assets' => $root.'assets/components/'.PKG_ABBR,
    'lexicon' => $root.'core/components/'.PKG_ABBR.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_ABBR.'/docs/',
);
unset($root);

$modx->initialize('mgr');
echo '<pre>';
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_ABBR,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_ABBR,false,true,'{core_path}components/'.PKG_ABBR.'/');

/* add snippet */
$modx->log(modX::LOG_LEVEL_INFO,'Adding in snippet.'); flush();
$snippet = $modx->newObject('modSnippet');
$snippet->fromArray(array(
    'id' => 0,
    'name' => 'PlainSearch',
    'description' => 'A plain and simple search component.',
    'snippet' => getSnippetContent($sources['source_core'].'/snippet.plainsearch.php'),
),'',true,true);
$properties = include $sources['build'].'properties.inc.php';
$snippet->setProperties($properties);


/* create snippet vehicle */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
);
$vehicle = $builder->createVehicle($snippet,$attr);
$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
));

$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

exit ();