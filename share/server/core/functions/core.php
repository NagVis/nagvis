<?php
/*****************************************************************************
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

$CORE = GlobalCore::getInstance();

/*
 * l() needs to be available in MainCfg initialization and config parsing,
 * but GlobalLanguage initialization relies on the main configuration in
 * some parts.
 * The cleanest way to solve this problem is to skip the i18n in the main
 * configuration init code until all needed values are initialized and then
 * initialize the i18n code.
 */

// ----------------------------------------------------------------------------

$_MAINCFG = new GlobalMainCfg();
$_MAINCFG->init();

/**
 * This is mainly a short access to config options. In the past the whole
 * language object method call was used all arround NagVis. This has been
 * introduced to keep the code shorter
 */
function cfg($sec, $key, $ignoreDefaults = false) {
    global $_MAINCFG;
    return $_MAINCFG->getValue($sec, $key, $ignoreDefaults);
}

function path($type, $loc, $var, $relfile = '') {
    global $_MAINCFG;
    return $_MAINCFG->getPath($type, $loc, $var, $relfile);
}

// ----------------------------------------------------------------------------

$_LANG = new GlobalLanguage();

/**
 * This is mainly a short access to localized strings. In the past the whole
 * language object method call was used all arround NagVis. This has been
 * introduced to keep the code shorter
 */
function l($txt, $vars = null) {
    global $_LANG;
    if (isset($_LANG)) {
        return $_LANG->getText($txt, $vars);
    }
    elseif ($vars !== null) {
        return GlobalLanguage::getReplacedString($txt, $vars);
    } else {
        return $txt;
    }
}

function curLang() {
    global $_LANG;
    return $_LANG->getCurrentLanguage();
}

// ----------------------------------------------------------------------------

/**
 * Initialize the backend management for all pages. But don't open backend
 * connections. This is only done when the pages request data from any backend
 */
$_BACKEND = new CoreBackendMgmt();

// ----------------------------------------------------------------------------
// some untilities

/**
 * @param array $arr
 * @param string $key
 * @param mixed|null $dflt
 * @return mixed|null
 */
function val($arr, $key, $dflt = null) {
    return isset($arr[$key]) ? $arr[$key] : $dflt;
}

/**
 * @param int $state
 * @return string
 */
function state_str($state) {
    switch ($state) {
        case UNCHECKED:
            return 'UNCHECKED';
        case UNREACHABLE:
            return 'UNREACHABLE';
        case DOWN:
            return 'DOWN';
        case UP:
            return 'UP';
        case PENDING:
            return 'PENDING';
        case UNKNOWN:
            return 'UNKNOWN';
        case CRITICAL:
            return 'CRITICAL';
        case WARNING:
            return 'WARNING';
        case OK:
            return 'OK';
        case ERROR:
        default:
            return 'ERROR'; // unspecified state
    }
}

/**
 * @param string $state_str
 * @return mixed
 */
function state_num($state_str) {
    $a = [
        'UNCHECKED'     => UNCHECKED,
        'UNREACHABLE'   => UNREACHABLE,
        'DOWN'          => DOWN,
        'UP'            => UP,
        // services
        'PENDING'       => PENDING,
        'UNKNOWN'       => UNKNOWN,
        'CRITICAL'      => CRITICAL,
        'WARNING'       => WARNING,
        'OK'            => OK,
        // generic
        'ERROR'         => ERROR
    ];
    return $a[$state_str];
}

/**
 * @param int $state
 * @return bool
 */
function is_host_state($state) {
    return $state == UNCHECKED || $state == UNREACHABLE || $state == DOWN || $state == UP;
}

/**
 * @return array
 * @throws NagVisException
 */
function listIconsets() {
    /** @var GlobalCore $CORE */
    global $CORE;
    return $CORE->getAvailableIconsets();
}

/**
 * @return array
 * @throws Exception
 */
function listBackendIds() {
    /** @var GlobalCore $CORE */
    global $CORE;
    return $CORE->getDefinedBackends();
}

/**
 * @return array
 * @throws NagVisException
 */
function listHeaderTemplates() {
    /** @var GlobalCore $CORE */
    global $CORE;
    return $CORE->getAvailableHeaderTemplates();
}

/**
 * @return array
 * @throws NagVisException
 */
function listHoverTemplates() {
    /** @var GlobalCore $CORE */
    global $CORE;
    return $CORE->getAvailableHoverTemplates();
}

/**
 * @return array
 * @throws NagVisException
 */
function listContextTemplates() {
    /** @var GlobalCore $CORE */
    global $CORE;
    return $CORE->getAvailableContextTemplates();
}

/**
 * @return array
 */
function listHoverChildSorters() {
    return [
        'a' => l('Alphabetically'),
        's' => l('State'),
        'k' => l('Keep original order'),
    ];
}

/**
 * @return array
 */
function listHoverChildOrders() {
    return [
        'asc'  => l('Ascending'),
        'desc' => l('Descending'),
    ];
}
