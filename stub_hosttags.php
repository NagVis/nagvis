<?php
// This file exists to make static analysis understand what NagVis includes at runtime. This file is
// created by Checkmk in real environments to hand over tag group information to NagVis.

global $mk_hosttags, $mk_auxtags;
$mk_hosttags = [];
$mk_auxtags = [];

function taggroup_title($group_id) {
    global $mk_hosttags;
    if (isset($mk_hosttags[$group_id]))
        return $mk_hosttags[$group_id][0];
    else
        return $group_id;
}

function taggroup_choice($group_id, $object_tags) {
    global $mk_hosttags;
    if (!isset($mk_hosttags[$group_id]))
        return false;
    foreach ($object_tags AS $tag) {
        if (isset($mk_hosttags[$group_id][2][$tag])) {
            // Found a match of the objects tags with the taggroup
            // now return an array of the matched tag and its alias
            return [$tag, $mk_hosttags[$group_id][2][$tag][0]];
        }
    }
    // no match found. Test whether or not a "None" choice is allowed
    if (isset($mk_hosttags[$group_id][2][null]))
        return [null, $mk_hosttags[$group_id][2][null][0]];
    else
        return null; // no match found
}

function all_taggroup_choices($object_tags) {
    global $mk_hosttags;
    $choices = [];
    foreach ($mk_hosttags AS $group_id => $group) {
        $choices[$group_id] = [
            'topic' => $group[0],
            'title' => $group[1],
            'value' => taggroup_choice($group_id, $object_tags),
        ];
    }
    return $choices;
}

?>
