<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the customcert element code's core interaction API.
 *
 * @package    customcertelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace customcertelement_code;

use mod_customcert\code_symbol_type;

/**
 * The customcert element code's core interaction API.
 *
 * @package    customcertelement_code
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class element extends \mod_customcert\element {
    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param \stdClass $data the form data.
     * @return string the json encoded array
     */
    public function save_unique_data($data) {
        return json_encode([
            'code_symbols_type' => $data->code_symbols_type,
        ]);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        global $DB;

        if ($preview) {

            $courseid = \mod_customcert\element_helper::get_courseid($this->id);
            $course = get_course($courseid);
            $code = \mod_customcert\certificate::generate_code(
                $this->code_symbol_type_from_coursefield($course)
            );
        } else {
            // Get the page.
            $page = $DB->get_record('customcert_pages', ['id' => $this->get_pageid()], '*', MUST_EXIST);
            // Get the customcert this page belongs to.
            $customcert = $DB->get_record('customcert', ['templateid' => $page->templateid], '*', MUST_EXIST);
            // Now we can get the issue for this user.
            $issue = $DB->get_record('customcert_issues', ['userid' => $user->id, 'customcertid' => $customcert->id],
                '*', IGNORE_MULTIPLE);
            $code = $issue->code;
        }

        \mod_customcert\element_helper::render_content($pdf, $this, $code);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     *
     * @return string the html
     */
    public function render_html() {
        $courseid = \mod_customcert\element_helper::get_courseid($this->id);
        $course = get_course($courseid);
        $code = \mod_customcert\certificate::generate_code(
            $this->code_symbol_type_from_coursefield($course)
        );

        return \mod_customcert\element_helper::render_html_content($this, $code);
    }

    /**
     * Helper function that returns the field value in a human-readable format.
     *
     * @param \stdClass $course the course we are rendering this for
     */
    protected function code_symbol_type_from_coursefield(\stdClass $course) : code_symbol_type {
        $handler = \core_course\customfield\course_handler::create();
        $datafieldarr = $handler->get_instance_data($course->id, true);
        $codetype = code_symbol_type::CIFRI_BUKVI;
        foreach($datafieldarr as $datacontroller) {
            if ('customcert_code_symbol_type' === $datacontroller->get_field()->get('shortname')) {
                $fromcourse = match(mb_strtoupper($datacontroller->export_value())) {
                    'ЦИФРЫ' => code_symbol_type::CIFRI,
                    default => code_symbol_type::CIFRI_BUKVI,
                };
                if ($fromcourse) {
                    $codetype = $fromcourse;
                }
                break;
            }
        }

        return $codetype;
    }
}
