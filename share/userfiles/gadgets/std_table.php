<?php
/*****************************************************************************
 *
 * std_table.php - Renders a table showing host and service object info
 *
 * Copyright (c) 2004-2015 NagVis Project (Contact: info@nagvis.org)
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

// Set colors
$table_colors = Array();
$table_colors['WARNING'] = "#FFFF00";
$table_colors['UNKNOWN'] = "#FFCC66";
$table_colors['UP']      = "#00FF00";
$table_colors['DOWN']    = "#FF0000";
$table_colors['ERROR']   = "#3498db";
$table_colors['HEADER']  = "#34495e";
$table_colors['EMPTY']   = "#eff0f1";

// Define Gadget UNIQUE name
$ident = $_GET['object_id'] . '-gadget';

// Set default parameters values
$show_header               = 1; // Show header or not (STATUS, DOWNTIME,...)
$show_subheader            = 1; // Show sub header or not (OK, WARNING, CRITICAL,...)
$show_all                  = 0; // Hide cells with value of 0
$group_states              = 1; // Downtime and ack states are with standard states
$size                      = 1; // Scale

// Only relevant for hostgroup gadgets
$show_service_states = 1;
$show_host_states = $_GET['type'] == 'hostgroup'
                    || $_GET['type'] == 'map'
                    || ($_GET['type'] == 'dyngroup' && $_GET['object_types'] == 'host');

// Get parameters from gadget_opts
if (isset($_GET['opts']) && ($_GET['opts'] != '')){
    preg_match_all('/(\w+)=(\w+)/', $_GET['opts'], $matches, PREG_SET_ORDER);
    for ($i = 0; $i < count($matches); $i++) {
        if ($matches[$i][1] == 'show_header') { $show_header = $matches[$i][2]; }
        if ($matches[$i][1] == 'show_subheader') { $show_subheader = $matches[$i][2]; }
        if ($matches[$i][1] == 'show_all') { $show_all = $matches[$i][2]; }
        if ($matches[$i][1] == 'group_states') { $group_states = $matches[$i][2]; }
        if ($matches[$i][1] == 'show_service_states') { $show_service_states = $matches[$i][2]; }
    }
}

// Determine scale from POST request
$size = $_GET['scale']/100;
if ($size < 0) { 
    $size = 1;
}

// Get object current state
$current_state = $_GET['state'];

// Get members of this element (Servicegroup, ...)
$members = json_decode($_POST['members'], True);

$show_states = Array();
if ($show_service_states)
    $show_states = array_merge($show_states, array('OK', 'WARNING', 'CRITICAL', 'UNKNOWN', 'ERROR'));
if ($show_host_states)
    $show_states = array_merge($show_states, array('UP', 'DOWN', 'UNREACHABLE', 'ERROR'));

// Init statistic array
$stats = Array();
foreach ($show_states AS $state) {
    $stats['S_'.$state] = 0;
    $stats['D_'.$state] = 0;
    $stats['A_'.$state] = 0;
}

// Count statistic for all members
foreach ($members as $member) {
    // Downtime
    if ($member['summary_in_downtime'] == 1 && $group_states == 0) {
        // FIXME: Check that the key exists
        $stats['D_'.$member['summary_state']] += 1;
    }
    // Acknowledged
    elseif ($member['summary_problem_has_been_acknowledged'] == 1 && $group_states == 0) {
        // FIXME: Check that the key exists
        $stats['A_'.$member['summary_state']] += 1;
    }
    // Standard state or all states if $group_states == 1
    else {
        // FIXME: Check that the key exists
        $stats['S_'.$member['summary_state']] += 1;
    }
}
// Determine sizes with scale
$padding = $size;
$font_size = 6 + $size * 2;
if ($show_subheader == "1") {
    $width = 21 + 6 * $size;
}
else {
    $width = 18 + 2 * $size;
}

// Write CSS
echo "<style>
/* Table style */
table[name=\"$ident\"] {
    border-collapse: separate;
    border-spacing: 0px;
    text-indent: 0px;
}

table[name=\"$ident\"] tbody {
    display:        table-row-group;
    vertical-align: middle;
    border-color:   inherit;
    table-layout:   fixed;
}

table[name=\"$ident\"] th {
    background: #34495e;
}

table[name=\"$ident\"] tr:first-child th:first-child {
    border-radius: 6px 0 0 0;
}

table[name=\"$ident\"] tr:first-child th:last-child {
    border-radius: 0 6px 0 0;
}

table[name=\"$ident\"] tr:first-child th:only-child {
    border-radius: 6px 6px 0 0;
}

table[name=\"$ident\"] td:last-child {
    border-right: 1px solid #ddd;
}

table[name=\"$ident\"] th:last-child {
    border-right: 1px solid #ddd;
}

table[name=\"$ident\"] tr:last-child td {
    border-bottom: 1px solid #ddd;
}

table[name=\"$ident\"] tr:last-child td:first-child {
    border-radius: 0 0 0 6px;
}

table[name=\"$ident\"] tr:last-child td:last-child {
    border-radius: 0 0 6px 0;
}

table[name=\"$ident\"] tr:last-child td:only-child {
    border-radius: 0 0 6px 6px;
}

table[name=\"$ident\"] td,
table[name=\"$ident\"] th,
table[name=\"$ident\"] tr {
    padding:        " . $padding . "px;
    text-align:     center;
    font-family:    'Open Sans', Helvetica;
    font-size:      " . $font_size . "px;
    border-left: 1px solid #ddd;
    border-top: 1px solid #ddd;
    color:          white;
}

table[name=\"$ident\"] td.table_stat {
    width: " . $width . "px;
    min-width: 18px;
    min-height: 18px;
}

/* Disable link underline in table */
div.icon a {
    text-decoration: none;
}

table[name=\"$ident\"] th.OK,
table[name=\"$ident\"] th.UP,
table[name=\"$ident\"] td.OK,
table[name=\"$ident\"] td.UP {
    background: ". $table_colors['UP']. ";
    color: #000000;
}
table[name=\"$ident\"] th.WARNING,
table[name=\"$ident\"] td.WARNING {
    background: ". $table_colors['WARNING']. ";
    color: #000000;
    font-weight: bold;
}

table[name=\"$ident\"] th.CRITICAL,
table[name=\"$ident\"] th.DOWN,
table[name=\"$ident\"] th.UNREACHABLE,
table[name=\"$ident\"] td.CRITICAL,
table[name=\"$ident\"] td.DOWN,
table[name=\"$ident\"] td.UNREACHABLE {
    background: ". $table_colors['DOWN']. ";
    color: #000000;
    font-weight: bold;
}

table[name=\"$ident\"] th.UNKNOWN,
table[name=\"$ident\"] td.UNKNOWN {
    background: ". $table_colors['UNKNOWN']. ";
    color: #000000;
    font-weight: bold;
}

table[name=\"$ident\"] th.ERROR,
table[name=\"$ident\"] td.ERROR {
    background: ". $table_colors['ERROR']. ";
    color: #000000;
    font-weight: bold;
}

table[name=\"$ident\"] td.EMPTY {
    background: ". $table_colors['EMPTY']. ";
    color: #666B85;
}
";
// Handle radius when header is hidden
if ($show_header == 0) {
    echo "
    table[name=\"$ident\"] tr:first-child td:first-child {
        border-top-left-radius: 6px;
    }

    table[name=\"$ident\"] tr:first-child td:last-child {
        border-top-right-radius: 6px;
    }

    table[name=\"$ident\"] tr:first-child td:only-child {
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
    }
    ";
}
echo "</style>";
// END of CSS

// Write Table
echo '<table name="'. $ident .'" class="table-gadget">';
if ($show_header == 1) {
    // Write Header
    echo "<thead>";
    echo "<tr>";
    $header1 = Array();
    $header1['S'] = "STATUS";
    if ($group_states == 0){
        $header1['D'] = "DOWNTIME";
        $header1['A'] = "ACKNOWLEDGED";
    }
    foreach ($header1 as $prefix => $title){
        $current_header = $prefix;
        $colspan = 0;
        foreach ($stats as $stat => $value){
            if (strpos($stat, $prefix . "_") === 0) {
                if ($value > 0 || $show_all == 1) {
                    $colspan ++;
                }
            }
        }
        if ($colspan > 0) {
            echo '<th colspan="'. $colspan .'">';
            echo $title;
            echo "</th>";
        }

    }
    echo "</tr>";
    echo "<tr>";
    if ($show_subheader == 1) {
        // Write Sub Header
        foreach ($stats as $stat => $value){
            if ($group_states == 0 || strpos($stat, "S_") === 0) {
                if ($value > 0 || $show_all == 1) {
                    $global_cls = (substr($stat, 2) == $current_state) ? $current_state : '';
                    echo '<th class="'.$global_cls.'">';
                    echo substr(substr(strchr($stat, "_"), 1), 0, 4);
                    echo "</th>";
                }
            }
        }
    }
    echo "</tr>";
    echo "</thead>";
}
// END of write header

// Write table body
echo "<tbody>";
echo '<tr>';
foreach ($stats as $stat => $value){
    if ($group_states == 0 || strpos($stat, "S_") === 0) {
        if ($value > 0 || $show_all == 1) {
            if ($value > 0) {
                $class = substr(strchr($stat, "_"), 1);
            }
            else {
                $class = 'EMPTY';
            }
            echo '<td class="'. $class .' table_stat">';
                echo $value;
            echo "</td>";
        }
    }
}
echo "</tr>";
echo "</tbody>";
// END of Write table body
echo "</table>";
exit(0);
?>
