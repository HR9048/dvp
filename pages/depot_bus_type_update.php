<?php
include '../includes/connection.php';
include '../includes/depot_top.php';

if (!isset($_SESSION['MEMBER_ID']) || !isset($_SESSION['TYPE']) || !isset($_SESSION['JOB_TITLE'])) {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to Login Page'); window.location = 'logout.php';</script>";
    exit;
}

if ($_SESSION['JOB_TITLE'] == 'Mech' || $_SESSION['JOB_TITLE'] == 'DM' || $_SESSION['JOB_TITLE'] == 'DME' || $_SESSION['JOB_TITLE'] == 'WM') {
    $division_id = $_SESSION['DIVISION_ID'];
    $depot_id = $_SESSION['DEPOT_ID'];

    // Fetch buses where any of the 3 fields are NULL or empty
    $query = "SELECT bus_number, make, emission_norms, model_type 
          FROM bus_registration 
          WHERE depot_name = $depot_id AND division_name = $division_id order by model_type asc";

    $result = mysqli_query($db, $query);
?>

    <h2>Update Details for Program</h2>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Bus Number</th>
                <th>Make</th>
                <th>Emission Norms</th>
                <th>Bus Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $bus_no = $row['bus_number'];
                $make = $row['make'];
                $emission = $row['emission_norms'];
                $model_type = $row['model_type'];
            ?>
                <tr id="row-<?= $bus_no ?>">
                    <td><?= $i++ ?></td>
                    <td><input type="text" value="<?= $bus_no ?>" readonly class="bus_no"></td>
                    <td><input type="text" value="<?= $make ?>" readonly class="make"></td>
                    <td><input type="text" value="<?= $emission ?>" readonly class="emission"></td>
                    <td>
                        <select class="model_type">
                            <option value="">-- Select --</option>
                        </select>
                        <span style="display: none;" class="saved">Saved: <?= $model_type ?: 'None' ?></span>
                    </td>
                    <td>
                        <button class="btn-update" onclick="updateRow('<?= $bus_no ?>', this)">Update</button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <script>
        // Mapping
        const modelMap = {
            Tata: {
                "BS-2": ["OLD", "NEW"],
                "BS-3": ["MIDI", "OLD", "NEW"],
                "BS-4": ["NEW"]
            },
            Leyland: {
                "BS-2": ["NEW"],
                "BS-3": ["OLD", "H-SERIES"],
                "BS-4": ["NEW"],
                "BS-6": ["NEW"]
            },
            Eicher: {
                "BS-3": ["NEW"],
                "BS-4": ["NEW"]
            },
            Corona: {
                "BS-3": ["NEW"]
            },
            Volvo: {
                "BS-6": ["NEW"]
            }
        };

        document.querySelectorAll('tr[id^="row-"]').forEach(row => {
            const make = row.querySelector('.make').value;
            const emission = row.querySelector('.emission').value;
            const current = row.querySelector('.saved').textContent.replace('Saved: ', '').trim();
            const select = row.querySelector('.model_type');

            if (modelMap[make] && modelMap[make][emission]) {
                modelMap[make][emission].forEach(opt => {
                    const o = document.createElement('option');
                    o.value = opt;
                    o.textContent = opt;
                    if (opt === current) o.selected = true;
                    select.appendChild(o);
                });
            } else {
                const o = document.createElement('option');
                o.value = "";
                o.textContent = "Not Available";
                select.appendChild(o);
                select.disabled = true;
                row.querySelector('.btn-update').disabled = true;
            }
        });

        // AJAX function to update a row
        function updateRow(busNumber, btnElement) {
            const row = btnElement.closest('tr');
            const modelType = row.querySelector('.model_type').value;

            if (!modelType) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Model Type Required',
                    text: 'Please select a model type before updating.'
                });
                return;
            }

            const formData = new FormData();
            formData.append("action", "update_model_type");
            formData.append("bus_number", busNumber);
            formData.append("model_type", modelType);

            console.log("Bus No:", busNumber);
            console.log("Model Type:", modelType);

            fetch('../includes/backend_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: data.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'Could not send request. Check console.'
                    });
                    console.error("Fetch Error:", err);
                });
        }
    </script>

<?php
} else {
    echo "<script type='text/javascript'>alert('Restricted Page! You will be redirected to " . $_SESSION['JOB_TITLE'] . " Page'); window.location = 'processlogin.php';</script>";
    exit;
}
include '../includes/footer.php';
?>