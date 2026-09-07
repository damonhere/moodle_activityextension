<?php
// This file is part of Moodle - http://moodle.org/
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
 * Value object describing the outcome of an eligibility::can_request() check.
 *
 * @package    local_quizextensionmanager
 * @copyright  2026 Damon Erickson <damon.erickson@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_quizextensionmanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Carries whether a request is currently allowed, and if not, a lang-string
 * key (plus optional args) explaining why, so both the quiz view page entry
 * point and the request form can explain a rejection to the student.
 */
class eligibility_result {

    /** @var bool Whether a new request is currently allowed. */
    public $allowed;

    /** @var string Lang string key (in local_quizextensionmanager) explaining a rejection. Empty when allowed. */
    public $reasoncode;

    /** @var array|null Optional args for the reason lang string. */
    public $reasonargs;

    /**
     * Constructor.
     *
     * @param bool $allowed whether a new request is currently allowed.
     * @param string $reasoncode lang string key (without component) explaining why not, if applicable.
     * @param array|null $reasonargs optional args for the reason lang string.
     */
    public function __construct(bool $allowed, string $reasoncode = '', ?array $reasonargs = null) {
        $this->allowed = $allowed;
        $this->reasoncode = $reasoncode;
        $this->reasonargs = $reasonargs;
    }

    /**
     * Get the human-readable rejection reason, or an empty string if allowed.
     *
     * @return string
     */
    public function get_reason_string(): string {
        if ($this->allowed || $this->reasoncode === '') {
            return '';
        }

        return get_string($this->reasoncode, 'local_quizextensionmanager', $this->reasonargs);
    }
}
