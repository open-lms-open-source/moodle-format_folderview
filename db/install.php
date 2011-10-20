<?php
/**
 * Steal topcoll formats
 *
 * @author Mark Nielsen
 * @package format_folderview
 */
function xmldb_format_folderview_install() {
    global $DB;

    $DB->set_field('course', 'format', 'folderview', array('format' => 'topcoll'));
}