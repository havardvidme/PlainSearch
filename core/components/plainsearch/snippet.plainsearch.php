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
 * A plain and simple search component.
 *
 * @package plainsearch
 */
$results = array(
	'output' => '',
	'search' => '',
	'total' => 0,
);

$tpl = $modx->getOption('tpl', $scriptProperties, '');
$tplWrapper = $modx->getOption('tplWrapper', $scriptProperties, '');
$searchKey = $modx->getOption('searchKey', $scriptProperties, 's');
$contexts = explode(',', $modx->getOption('contexts', $scriptProperties, $modx->resource->get('context_key')));
$onlySearchable = (boolean) $modx->getOption('onlySearchable', $scriptProperties, true);
$includeContent = !empty($includeContent) ? true : false;
$excludeWeblinks = empty($excludeWeblinks) ? true : false;
$excludeDeleted = (boolean) $modx->getOption('excludeDeleted', $scriptProperties, false);
$excludeUnpublished = (boolean) $modx->getOption('excludeUnpublished', $scriptProperties, false);

$search = $modx->getOption($searchKey, $_GET, '');
if (!empty($search)) {
	$fields = array(
		'pagetitle',
		'longtitle',
		'description',
		'introtext',
		'content',
	);
	$words = explode(' ', strtolower($search));

	$c = $modx->newQuery('modResource');
	$columns = $includeContent ? $modx->getSelectColumns('modResource', 'modResource') : $modx->getSelectColumns('modResource', 'modResource', '', array('content'), true);
	$c->select($columns);

	$where = array();
	if (!empty($search)) {
		$ands = array();
		foreach ($words as $word) {
			$ors = array();
			foreach ($fields as $i => $field) {
				$prefix = $i == 0 ? '' : 'OR:';
				$ors[$prefix.$field.':LIKE'] = '%'.$word.'%';
			}
			$ands[] = $ors;
		}
		$where[] = $ands;
	}
	$additional = array(
		'context_key:IN' => $contexts,
	);
	if ($onlySearchable) $additional['searchable'] = 1;
	if ($excludeDeleted) $additional['deleted'] = 0;
	if ($excludeUnpublished) $additional['published'] = 1;
	if ($excludeWeblinks) $additional['class_key:!='] = 'modWebLink'; //modDocument

	$where[] = $additional;
	$c->where($where);

	$docs = $modx->getCollection('modResource', $c);
	if ($docs) {
		$idx = 0;
		$total = count($docs);
		foreach ($docs as $doc) {
			$fields = $doc->toArray('', false, true);
			$fields['idx'] = ++$idx;
			$fields['total'] = $total;
			$results['output'] .= empty($tpl) ? '<pre>'.print_r($fields, true).'</pre>' : $modx->getChunk($tpl, $fields);
		}
		$results['total'] = $total;
	}
	$results['search'] = $search;
}
return empty($tplWrapper) ? $results['output'] : $modx->getChunk($tplWrapper, $results);