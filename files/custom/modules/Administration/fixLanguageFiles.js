/*********************************************************************************
 * Fix Language Files
 * Kenneth Brill (kbrill@sugarcrm.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY KENNETH BRILL, KENNETH BRILL DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 *
 * @category   JavaScript
 * @package    fixLanguageFiles
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2015-2016 SugarCRM
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    3.0
 */
function toggleBarVisibility() {
    var e = document.getElementById("bar_blank");
    e.style.display = (e.style.display == "block") ? "none" : "block";
    var cb = document.getElementById("checkboxes");
    cb.style.display = (e.style.display == "block") ? "none" : "block";
    var sb = document.getElementById("start_button");
    sb.style.display = 'none';
}

function createRequestObject() {
    var http;
    if (navigator.appName == "Microsoft Internet Explorer") {
        http = new ActiveXObject("Microsoft.XMLHTTP");
    }
    else {
        http = new XMLHttpRequest();
    }
    return http;
}

function sendRequest() {
    var http = createRequestObject();
    http.open("GET", "index.php?entryPoint=fixLanguageFiles_progress");
    http.onreadystatechange = function () { handleResponse(http); };
    http.send(null);
}

function handleResponse(http) {
    var response;
    if (http.readyState == 4) {
        response = http.responseText;
        document.getElementById("bar_color").style.width = response + "%";
        document.getElementById("status").innerHTML = response + "%";

        if (response < 100) {
            setTimeout("sendRequest()", 5000);
        }
        else {
            toggleBarVisibility();
            document.getElementById("status").innerHTML = "Done.";
        }
    }
}

function startTest() {
    toggleBarVisibility();
    setTimeout("sendRequest()", 5000);
    document.getElementById("fixLanguageFiles").submit();
}

(function () {
    document.getElementById("start_button").onclick = startTest;
})();