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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

class block_interactions_chart extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_interactions_chart');
    }

    public function get_content() {
        global $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        // SQL query to get current user's interactions
        $currentUserQuery = "
            SELECT COUNT(*) AS num_interactions
            FROM {logstore_standard_log}
            WHERE userid = :userid
              AND component IN ('core_course', 'mod_forum', 'mod_assign', 'mod_quiz', 'mod_resource', 'mod_page', 'mod_book')
        ";

        // SQL query to get total interactions for all users
        $totalInteractionsQuery = "
            SELECT SUM(num_interactions) AS total_interactions
            FROM (
                SELECT COUNT(*) AS num_interactions
                FROM {logstore_standard_log}
                WHERE component IN ('core_course', 'mod_forum', 'mod_assign', 'mod_quiz', 'mod_resource', 'mod_page', 'mod_book')
                GROUP BY userid
            ) AS interactions_by_user
        ";

        $currentUserInteractions = $DB->get_record_sql($currentUserQuery, ['userid' => $USER->id]);
        $totalInteractions = $DB->get_record_sql($totalInteractionsQuery);

        // Calculate percentage of current user's interactions compared to total interactions
        if ($totalInteractions->total_interactions > 0) {
            $percentageCurrentUser = ($currentUserInteractions->num_interactions / $totalInteractions->total_interactions) * 100;
        } else {
            $percentageCurrentUser = 0;
        }

        // Chart.js code to render pie chart
        $this->content = new stdClass;
        $this->content->text = '
            <div>
                <canvas id="interactionsPieChart" width="200px%" height="200px" aria-label="chart"></canvas>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var ctx = document.getElementById("interactionsPieChart").getContext("2d");
                    var interactionsPieChart = new Chart(ctx, {
                        type: "doughnut",
                        data: {
                            labels: ["Current User", "Other Users"],
                            datasets: [{
                                label: "Interactions",
                                data: [' . round($percentageCurrentUser, 2) . ', ' . round(100 - $percentageCurrentUser, 2) . '],
                                backgroundColor: ["#1F7BF4", "#FFA043"],
                                hoverOffset: 5
                            }]
                        },
                        options: {
                            responsive: true,
                        }
                    });
                });
            </script>
        ';
        $this->content->footer = '';

        return $this->content;
    }
}
