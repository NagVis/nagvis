<?php
/*****************************************************************************
 *
 * config.php - Redirects the requests to the frontend index file
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
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
 
header("Location: ". ((isset($_SERVER["HTTPS"])) ? 'https://': 'http://') . $_SERVER['HTTP_HOST'] 
	. ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') ? ':'.$_SERVER['SERVER_PORT']: '')
	. rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
    . "/nagvis/index.php".(($_SERVER["QUERY_STRING"] != '') ? '?':'').$_SERVER["QUERY_STRING"]);
?>