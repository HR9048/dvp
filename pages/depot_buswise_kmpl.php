<?php
include '../includes/connection.php';
include '../includes/depot_sidebar.php';
// Check if session variables are set
if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}
if ($_SESSION['TYPE'] == 'DEPOT' && $_SESSION['JOB_TITLE'] == 'DM' ||$_SESSION['JOB_TITLE'] == 'Bunk') {
    // Allow access
?>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        background-color: #f4f4f4;
    }

    .container {
        max-width: 80%;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .form-group label {
        flex: 0 0 20%;
        margin-right: 10px;
    }

    .form-group input,
    .form-group textarea {
        flex: 1;
        padding: 8px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .form-group input[readonly] {
        background-color: #e9ecef;
    }

    .form-group textarea {
        resize: vertical;
        height: 80px;
    }

    .form-group .edit-checkbox {
        margin-left: 10px;
    }

    button {
        padding: 10px 15px;
        background-color: #5cb85c;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    button:hover {
        background-color: red;
    }

    .hidden {
        display: none;
    }
</style>
</head>

<body>
    <div class="container">
        <h2>Bus Operation Logsheet</h2>
        <form id="logsheet_form" action="submit_logsheet.php" method="post">
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="logsheet_no">Logsheet No:</label>
                        <input type="text" id="logsheet_no" name="logsheet_no" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="schedule_no">Schedule Number:</label>
                        <input type="text" id="schedule_no" name="schedule_no" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="bus_number">Bus Number:</label>
                        <input type="text" id="bus_number" name="bus_number" required readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('bus_number')"> Change
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="driver_no1">Driver No 1:</label>
                        <input type="text" id="driver_no1" name="driver_no1" required readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('driver_no1')"> Change
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="driver_no2">Driver No 2:</label>
                        <input type="text" id="driver_no2" name="driver_no2" readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('driver_no2')"> Change
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="driver_no1">Driver 1 Name:</label>
                        <input type="text" id="driver_1_name" name="driver_1_name" required readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('driver_1_name')"> Change
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="driver_no2">Driver 2 Name:</label>
                        <input type="text" id="driver_2_name" name="driver_2_name" readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('driver_2_name')"> Change
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="km_operated">Kilometers Operated:</label>
                        <input type="number" id="km_operated" name="km_operated" required readonly>
                        <input type="checkbox" class="edit-checkbox" onclick="toggleReadonly('km_operated')"> Change
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="hsd">HSD:</label>
                        <input type="number" id="hsd" name="hsd" required >
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="defects">Defects Noted:</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" id="defect_brakes" name="defects[]" value="Brakes"> Brakes</label>
                    <label><input type="checkbox" id="defect_lights" name="defects[]" value="Lights"> Lights</label>
                    <label><input type="checkbox" id="defect_engine" name="defects[]" value="Engine"> Engine</label>
                    <label><input type="checkbox" id="defect_tires" name="defects[]" value="Tires"> Tires</label>
                    <label><input type="checkbox" id="defect_others" name="defects[]" value="Others"
                            onclick="toggleOthersTextArea()"> Others</label>
                </div>
            </div>
            <div class="form-group hidden" id="others_textarea">
                <label for="other_defects">Please specify:</label>
                <textarea id="other_defects" name="other_defects"></textarea>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks:</label>
                <textarea id="remarks" name="remarks"></textarea>
            </div>
            <div class="form-group" style="justify-content: center; ">
                <button type="submit">Submit Logsheet</button>
            </div>
        </form>
    </div>
    <script>
        function toggleReadonly(fieldId) {
            var field = document.getElementById(fieldId);
            field.readOnly = !field.readOnly;
        }

        function toggleOthersTextArea() {
            var othersCheckbox = document.getElementById('defect_others');
            var othersTextArea = document.getElementById('others_textarea');
            if (othersCheckbox.checked) {
                othersTextArea.classList.remove('hidden');
            } else {
                othersTextArea.classList.add('hidden');
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('schedule_no').addEventListener('change', function () {
                var schedule_no = this.value;

                if (schedule_no) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'fetch_schdules.php', true);
                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                    xhr.onload = function () {
                        if (this.status == 200) {
                            var data = JSON.parse(this.responseText);

                            if (Object.keys(data).length !== 0) {
                                document.getElementById('bus_number').value = data.bus_number || '';
                                document.getElementById('driver_no1').value = data.driver_token_1 || '';
                                document.getElementById('driver_no2').value = data.driver_token_2 || '';
                                document.getElementById('driver_1_name').value = data.emp_name1 || '';
                                document.getElementById('driver_2_name').value = data.emp_name2 || '';
                                document.getElementById('km_operated').value = data.sch_km || '';
                                document.getElementById('hsd').value = data.hsd || '';
                            } else {
                                document.getElementById('bus_number').value = '';
                                document.getElementById('driver_no1').value = '';
                                document.getElementById('driver_no2').value = '';
                                document.getElementById('driver_1_name').value = '';
                                document.getElementById('driver_2_name').value = ''
                                document.getElementById('km_operated').value = '';
                                document.getElementById('hsd').value = '';
                            }
                        }
                    };
                    xhr.send('schedule_no=' + schedule_no);
                } else {
                    document.getElementById('bus_number').value = '';
                    document.getElementById('driver_no1').value = '';
                    document.getElementById('driver_no2').value = '';
                    document.getElementById('driver_1_name').value = '';
                    document.getElementById('driver_2_name').value = '';
                    document.getElementById('km_operated').value = '';
                    document.getElementById('hsd').value = '';
                }
            });
        });
    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>