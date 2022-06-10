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
 * Main Functions
 *
 * @package    local_leeloolxp_lct
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Attempt submited event for quiz module.
 * @param mod_quiz\event\attempt_submitted $event
 * @return mixed string if ok true if license issue.
 */
function local_leeloolxp_lct_attempt_submitted(mod_quiz\event\attempt_submitted $event) {

    global $CFG;
    require_once($CFG->dirroot . '/lib/filelib.php');

    $certitrackerenable = get_config('local_leeloolxp_lct')->certitrackerenable;

    if ($certitrackerenable == '0') {
        return true;
    }

    $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
    $quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
    $quizname = $quiz->name;

    $leeloolxplicense = get_config('local_leeloolxp_lct')->license;

    $url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
    $postdata = '&license_key=' . $leeloolxplicense;

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HEADER' => false,
        'CURLOPT_POST' => 1,
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        return true;
    }

    $infoleeloolxp = json_decode($output);

    $leeloolxpurl = $infoleeloolxp->data->install_url;

    /* get task_id from teamnio */
    $url = $leeloolxpurl . "/admin/sync_moodle_course/get_task_id_by_name/" . urlencode($quizname);

    $postdata = '';

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HTTPHEADER' => array(
            'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
        )
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        return true;
    }

    $taskid = $output;

    date_default_timezone_set("America/Costa_Rica"); // GMT-6.

    $workingdate = date('Y-m-d');

    $trackerstopmessage = get_string('tracker_stop_message', 'local_leeloolxp_lct'); // Tracking stop on exam.
    echo '<div class="tracking_startedpopupcontainer"><div class="tracking_startedpopup"><h1 id="tracking_text"></h1></div></div>';

    echo '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/lct/css/lct.css' . '" />
    <script type="text/javascript" src="https://leeloolxp.com/socket_server/reconnecting-websocket.js"></script>
    <script type="text/javascript">
        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(" ");
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == " ") {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        var quiztracking = sessionStorage.getItem("quiztracking");
        if (quiztracking == 1) {
            var MyDate = new Date();
            var MyDateString;
            var teamnio_url = "' . $leeloolxpurl . '";
            MyDate.setDate(MyDate.getDate());
            MyDateString = MyDate.getFullYear() + "-" + ("0" + (MyDate.getMonth() + 1)).slice(-2) + "-" +
             ("0" + MyDate.getDate()).slice(-2);
            var myArray = {};
            myArray.task_id = "' . $taskid . '";
            myArray.working_date = ' . $workingdate . ';
            myArray.status = "0";
            myArray.task_type = "tct";
            myArray.user_id = sessionStorage.getItem("user_id");

            var wsUri = "wss://teamnio.com/wssteamnio"; // websocket url
            websocket = new ReconnectingWebSocket(wsUri); // socket reconnect
            websocket.onopen = function(ev) {
                var message_input = JSON.stringify(myArray);
                var msg = {
                    type: "quiztype",
                    message: JSON.parse(message_input),
                };
                websocket.send(JSON.stringify(msg)); // message send
            }
            websocket.onmessage = function(ev) {
                var response = JSON.parse(ev.data);
                console.log(response);
                if (response.message.status == "1") {
                    //submit_quiz_frm();
                }
            };
            sessionStorage.setItem("status_image", "gray"); // gray tracking status again
            document.getElementById("tracking_text").innerHTML = "' . $trackerstopmessage . '";
            sessionStorage.setItem("quiztracking", 0);
            setCookie("quiztracking", 0, 1);
        }
    </script>';

    for ($i = 0; $i < 50000; $i++) {
        echo "<div></div>";
    }
    return true; // Finaly return true.
}

/**
 * Attempt started event for quiz module.
 * @param mod_quiz\event\attempt_started $event
 * @return mixed string if ok true if license issue.
 */
function local_leeloolxp_lct_attempt_started(mod_quiz\event\attempt_started $event) {

    global $DB;
    global $USER;
    global $CFG;
    require_once($CFG->dirroot . '/lib/filelib.php');

    $useremail = $USER->email; // User email from moodle global.
    $username = $USER->username; // Username from moodle global.

    $useremailbase = base64_encode($useremail);
    $usernamebase = base64_encode($username);

    $course = $DB->get_record('course', array('id' => $event->courseid));

    $certitrackerenable = get_config('local_leeloolxp_lct')->certitrackerenable;

    if ($certitrackerenable == '0') {
        return true;
    }

    $attemptid = $event->objectid;

    if (isset($attemptid) && isset($attemptid) != '') {
        $checksynced = $DB->get_record_sql(
            "SELECT
                count(sync.teamnio_task_id) synced
            FROM {quiz_attempts} a
            left join {course_modules} cm
                on a.quiz = cm.instance
            left join {modules} m
                on m.id = cm.module
            left join {tool_leeloolxp_sync} sync
                on sync.activityid = cm.id
            where a.id = ? and m.name = ? and sync.enabled = ?",
            array($attemptid, 'quiz', 1)
        );

        if ($checksynced->synced == 0) {
            return true;
        }
    } else {
        return true;
    }

    $certitrackeruserenable = get_config('local_leeloolxp_lct')->certitrackeruserenable;

    if ($certitrackeruserenable) {
        $usercreateflag = 'yes';
    } else {
        $usercreateflag = 'no';
    }

    $leeloolxplicense = get_config('local_leeloolxp_lct')->license;

    $url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
    $postdata = '&license_key=' . $leeloolxplicense;

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HEADER' => false,
        'CURLOPT_POST' => 1,
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        return true;
    }

    $infoleeloolxp = json_decode($output);

    $leeloolxpurl = $infoleeloolxp->data->install_url;

    $userexistonteamnio = local_leeloolxp_lct_check_user_teamnio($useremailbase, $leeloolxpurl);

    if ($userexistonteamnio == '0') {
        if ($usercreateflag == 'no') {
            return true;
        }
    }

    $url = $leeloolxpurl . '/admin/sync_moodle_course/check_user_lct_status_by_email/' . $useremailbase;
    $curl = new curl;
    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HEADER' => false,
        'CURLOPT_POST' => 1,
        'CURLOPT_HTTPHEADER' => array(
            'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
        )
    );
    if (!$userstatusonteamnio = $curl->post($url, $postdata, $options)) {
        return true;
    }
    if ($userstatusonteamnio == 0) {
        return true;
    }

    $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
    $quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
    $quizname = $quiz->name; // Moodle quiz  name.
    $course = $DB->get_record('course', array('id' => $event->courseid));
    $groupnamequery = $DB->get_record('groups', array('courseid' => $course->id));

    if (!empty($groupnamequery)) {
        $groupname = $groupnamequery->name;
    } else {
        $groupname = '';
    }

    $url = $leeloolxpurl .
        "/admin/sync_moodle_course/create_task_version/?task_name=" . urlencode($quizname) .
        "&username=" . urlencode($usernamebase) .
        "&group_name=" . urlencode($groupname) .
        '&email=' . urlencode($useremailbase) .
        '&activity_id=' . $event->contextinstanceid;

    $postdata = '';

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HTTPHEADER' => array(
            'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
        )
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        return true;
    }

    $taskarray = json_decode($output);

    if ($output == '0' || !isset($taskarray->task_id)) {
        return true;
    }

    $taskid = $taskarray->task_id;

    $ok = get_string('ok', 'local_leeloolxp_lct');
    $cancel = get_string('cancel', 'local_leeloolxp_lct');

    date_default_timezone_set("America/Costa_Rica"); // GMT-6.
    $workingdate = date('Y-m-d');
    $notloginmessage = get_string('not_login_message', 'local_leeloolxp_lct'); // You are not login on tracker, please login.
    $trackerstartmessage = get_string('tracker_start_message', 'local_leeloolxp_lct'); // Tracking started.
    echo '<div class="tracking_startedpopupcontainer"><div class="tracking_startedpopup"><h1 id="tracking_text"></h1></div></div>';

    echo '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/local/lct/css/lct.css' . '" />
    <script type="text/javascript" src="https://leeloolxp.com/socket_server/reconnecting-websocket.js"></script>
    <script type="text/javascript">
        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(" ");
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == " ") {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        setCookie("quiztracking", 0, 1);
        sessionStorage.setItem("quiztracking", 0);
        var MyDate = new Date();
        var MyDateString;
        var teamnio_url = "' . $leeloolxpurl . '";
        MyDate.setDate(MyDate.getDate());
        MyDateString = MyDate.getFullYear() + "-" + ("0" + (MyDate.getMonth() + 1)).slice(-2) + "-" +
         ("0" + MyDate.getDate()).slice(-2);
        var myArray = {};
        myArray.task_id = "' . $taskid . '";

        myArray.working_date = ' . $workingdate . ';
        myArray.status = "1";

        function check_login(email) {
            var result = "";
            var characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            var charactersLength = characters.length;
            for (var i = 0; i < 5; i++) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }

            var xhttp_S = new XMLHttpRequest();
            xhttp_S.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    console.log(this.responseText);
                    if (this.responseText == "0") {
                        document.getElementById("tracking_text").innerHTML = "' .
        $notloginmessage . '<div class=\'lct_buttons\'>
                        <button onclick=\'check_login(\"' . $useremailbase . '\")\'>' . $ok . '</button>
                        <button onclick=\'location.href = \"' . $CFG->wwwroot . '\";\'>' . $cancel . '</button></div>";

                        window.stop();
                    } else {
                        myArray.user_id = this.responseText;
                        sessionStorage.setItem("user_id", myArray.user_id);
                        var wsUri = "wss://teamnio.com/wssteamnio";
                        websocket = new ReconnectingWebSocket(wsUri);
                        websocket.onopen = function(ev) {
                            var message_input = JSON.stringify(myArray);
                            var msg = {
                                type: "quiztype",
                                message: JSON.parse(message_input),
                            };
                            websocket.send(JSON.stringify(msg));
                        }
                        websocket.onmessage = function(ev) {
                            var response = JSON.parse(ev.data);
                            console.log(response);
                            sessionStorage.setItem("quiztracking", 1);
                            setCookie("quiztracking", 1, 1);
                            document.getElementById("tracking_text").innerHTML = "' . $trackerstartmessage . '";
                            location.reload();
                        };
                        return true;
                    }
                }
            };
            xhttp_S.open("GET", teamnio_url + "/admin/sync_moodle_course/login_status/?rand=" +
                result + "&user_email=" + email +
                "&installlogintoken=' . $_COOKIE['installlogintoken'] . '", true);
            xhttp_S.send();
            //window.stop();
            //return true;
            return false;
        }
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                myArray.user_id = this.responseText;
                sessionStorage.setItem("user_id", myArray.user_id);
                var logged_in_or_not = check_login("' . $useremailbase . '");
                //console.log("check_login");
                console.log(logged_in_or_not);
                if (logged_in_or_not) {
                    var wd = "";
                    var wsUri = "wss://teamnio.com/wssteamnio";
                    websocket = new ReconnectingWebSocket(wsUri);
                    websocket.onopen = function(ev) {
                        var message_input = JSON.stringify(myArray);
                        var msg = {
                            type: "quiztype",
                            message: JSON.parse(message_input),
                        };
                        websocket.send(JSON.stringify(msg));
                    }
                    websocket.onmessage = function(ev) {
                        var response = JSON.parse(ev.data);
                        console.log(response);

                    };
                    document.getElementById("tracking_text").innerHTML = "' .
        $trackerstartmessage . '<div class=\'lct_buttons\'>
                     <button onclick=\'location.reload();\'>' . $ok . '</button></div> "
                    sessionStorage.setItem("status_image", "orange");
                    websocket.onerror = function(ev) {
                        console.log(ev);
                    };
                    websocket.onclose = function(ev) {
                        alert("Closed");
                    };
                } else {

                    document.getElementById("tracking_text").innerHTML = "' .
        $notloginmessage . '<div class=\'lct_buttons\'>
                     <button onclick=\'check_login(\"' . $useremailbase . '\")\'>'
        . $ok . '</button><button onclick=\'location.href = \"' . $CFG->wwwroot . '\";\'>'
        . $cancel . '</button></div> "
                    window.stop();

                }
            }
        };
        xhttp.open("GET", teamnio_url + "/admin/sync_moodle_course/teamnio_user/?username=' .
        $usernamebase . '&expires=123&user_email=' . $useremailbase . '&name='
        . base64_encode(fullname($USER)) . '&installlogintoken='
        . $_COOKIE['installlogintoken'] . '", true);
        xhttp.send();
    </script>';
    die;
    // For delay to execute websocket.
    for ($i = 0; $i < 50000; $i++) {
        echo "<div></div>";
    }

    return true;
}

/**
 * Check user on Leeloo LXP.
 * @param string $useremailbase useremailbase
 * @param string $leeloolxpurl leeloolxpurl
 * @return mixed string
 */
function local_leeloolxp_lct_check_user_teamnio($useremailbase, $leeloolxpurl) {
    $url = $leeloolxpurl . '/admin/sync_moodle_course/check_user_by_email/' . $useremailbase;

    $postdata = '';

    global $CFG;
    require_once($CFG->dirroot . '/lib/filelib.php');

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HTTPHEADER' => array(
            'Leeloolxptoken: ' . get_config('local_leeloolxpapi')->leelooapitoken . ''
        )
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        return true;
    }

    return $output;
}

/**
 * On Attempt Abandoned.
 * @param mod_quiz\event\attempt_abandoned $event
 * @return bool true
 */
function local_leeloolxp_lct_attempt_abandoned(mod_quiz\event\attempt_abandoned $event) {
    return true;
}

/**
 * Before Footer Show.
 * @return bool true
 */
function local_leeloolxp_lct_before_footer() {

    global $PAGE, $USER;

    $actuallink = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    if (strpos($actuallink, 'quiz/attempt.php?attempt') !== false) {
        if ($_COOKIE['quiztracking'] != 1) {
            $params = array(
                'objectid' => $_GET['attempt'],
                'relateduserid' => $USER->id,
                'courseid' => $PAGE->course->id,
                'context' => context_system::instance(),
            );

            $event = \mod_quiz\event\attempt_started::create($params);
            $event->trigger();
        }
    }

    return true;
}
