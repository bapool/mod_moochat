<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for mod_moochat
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_moochat_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add future upgrade steps here
    // Example:
    // if ($oldversion < 2025103002) {
    //     // Upgrade code here
    //     upgrade_mod_savepoint(true, 2025103002, 'moochat');
    // }

    return true;
}