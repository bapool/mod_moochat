<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Add moochat instance
 */
function moochat_add_instance($moochat) {
    global $DB;
    
    $moochat->timecreated = time();
    $moochat->timemodified = time();
    
    $moochat->id = $DB->insert_record('moochat', $moochat);
    
    return $moochat->id;
}

/**
 * Update moochat instance
 */
function moochat_update_instance($moochat) {
    global $DB;
    
    $moochat->timemodified = time();
    $moochat->id = $moochat->instance;
    
    return $DB->update_record('moochat', $moochat);
}

/**
 * Delete moochat instance
 */
function moochat_delete_instance($id) {
    global $DB;
    
    if (!$moochat = $DB->get_record('moochat', array('id' => $id))) {
        return false;
    }
    
    // Delete usage records
    $DB->delete_records('moochat_usage', array('moochatid' => $id));
    
    // Delete the instance
    $DB->delete_records('moochat', array('id' => $id));
    
    return true;
}

/**
 * Supported features
 */
function moochat_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
	//case FEATURE_MOD_ARCHETYPE:
          //  return MOD_ARCHETYPE_ASSIGNMENT;
        default:
            return null;
    }
}

/**
 * Serve the files from the moochat file areas
 */
function mod_moochat_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    
    if ($filearea !== 'avatar') {
        return false;
    }
    
    require_login($course, false, $cm);
    
    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = '/';
    
    $file = $fs->get_file($context->id, 'mod_moochat', $filearea, 0, $filepath, $filename);
    
    if (!$file || $file->is_directory()) {
        return false;
    }
    
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
/**
 * Return the content to display inline on course page
 */
function moochat_get_coursemodule_info($coursemodule) {
    global $DB, $PAGE;
    
    $moochat = $DB->get_record('moochat', array('id' => $coursemodule->instance), '*', MUST_EXIST);
    
    $info = new cached_cm_info();
    $info->name = $moochat->name;
    
    if ($moochat->display == 1) {
        // Inline display mode
        $context = context_module::instance($coursemodule->id);
        
        // Get avatar URL if exists
        $avatarurl = null;
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_moochat', 'avatar', 0, 'filename', false);
        if (!empty($files)) {
            $file = reset($files);
            $avatarurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
        }
        
        // Include JavaScript
        //$PAGE->requires->js_call_amd('mod_moochat/chat', 'init', array($moochat->id));
        
        // Build inline content
        $content = '';
        $content .= '<div class="moochat-activity-container">';
        
        // Left side - Avatar and info
        $content .= '<div class="moochat-sidebar">';
        if ($avatarurl) {
            $content .= '<div class="moochat-avatar-large">';
            $content .= html_writer::img($avatarurl, $moochat->name, array('width' => $moochat->avatarsize, 'height' => $moochat->avatarsize));
            $content .= '</div>';
        }
        $content .= '<h3 class="moochat-sidebar-title">' . format_string($moochat->name) . '</h3>';
        $content .= '<div class="moochat-remaining-sidebar" id="moochat-remaining-' . $moochat->id . '"></div>';
        $content .= '</div>'; // End sidebar
        
        // Right side - Chat interface
        $content .= '<div class="moochat-chat-area">';
        $content .= '<div class="moochat-interface" id="moochat-' . $moochat->id . '">';
        
        // Chat messages area
        $content .= '<div class="moochat-messages" id="moochat-messages-' . $moochat->id . '">';
        $content .= '<p class="moochat-welcome">' . get_string('startchat', 'moochat') . '</p>';
        $content .= '</div>';
        
        // Input area
        $content .= '<div class="moochat-input-area">';
        $content .= '<textarea id="moochat-input-' . $moochat->id . '" class="moochat-input" placeholder="' . 
                   get_string('typemessage', 'moochat') . '" rows="3"></textarea>';
        
        // Buttons
        $content .= '<div class="moochat-buttons">';
        $content .= '<button id="moochat-send-' . $moochat->id . '" class="btn btn-primary moochat-send">' . 
                   get_string('send', 'moochat') . '</button>';
        $content .= '<button id="moochat-clear-' . $moochat->id . '" class="btn btn-secondary moochat-clear">' . 
                   get_string('clear', 'moochat') . '</button>';
        $content .= '</div>'; // End buttons
        
        $content .= '</div>'; // End input area
        $content .= '</div>'; // End moochat-interface
        $content .= '</div>'; // End chat-area
        $content .= '</div>'; // End container
        
        $info->content = $content;
    }
    
    return $info;
}
/**
 * Callback to add JS when course page loads
 */
function moochat_cm_info_view(cm_info $cm) {
    global $PAGE, $DB;
    
    $moochat = $DB->get_record('moochat', array('id' => $cm->instance));
    
    if ($moochat && $moochat->display == 1) {
        // Only load JS for inline display mode
        $PAGE->requires->js_call_amd('mod_moochat/chat', 'init', array($moochat->id));
    }
}
