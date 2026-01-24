$(document).ready(function () {
    // ✅ Fetch Data button click
    $("#overallDashboardForm button.btn-primary").on("click", function () {
        var startDate = $("#startDate").val();
        var division = $("#division_id").val();
        var depot = $("#depot_id").val();

        let missingFields = [];

        if (!startDate) missingFields.push("Start Date");
        if (!division) missingFields.push("Division");
        if (!depot) missingFields.push("Depot");

        if (missingFields.length > 0) {
            Swal.fire({
                icon: "warning",
                title: "Required Fields Missing!",
                text: "Please fill: " + missingFields.join(", "),
                confirmButtonColor: "#d33"
            });
            return; // stop execution if missing fields
        }

        var formData = $("#overallDashboardForm").serialize();

        $.ajax({
            url: "database/fetch_overalldashboard_data.php",
            type: "POST",
            data: formData,
            beforeSend: function () {
                $("#overallDashboard").html("<p><b>Loading data...</b></p>");
            },
            success: function (response) {
                $("#overallDashboard").html(response);
            },
            error: function (xhr, status, error) {
                $("#overallDashboard").html(
                    "<p style='color:red;'>Error fetching data: " + error + "</p>"
                );
            }
        });
    });

    // ✅ Clear Filters button click
    $("#clearFilters").on("click", function () {
        $("#overallDashboardForm")[0].reset(); // reset form fields
        $("#overallDashboard").empty(); // clear the results div
    });
});

document.getElementById("date-selector").addEventListener("change", function () {
    let selectedDate = this.value;
    let today = new Date().toISOString().split("T")[0];

    if (selectedDate > today) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Date Selection',
            text: 'You cannot select a future date!',
        });
        this.value = today; // Reset to today's date
        return;
    }

    updateDepartureHeading(selectedDate);
    fetchLiveDepartures();
});

function updateDepartureHeading(selectedDate) {
    let today = new Date().toISOString().split("T")[0];
    let heading = document.getElementById("departure-heading");

    if (selectedDate === today) {
        heading.innerHTML =
            `DEPARTURES AS ON <span id="current-time"></span> @ <span id="current-time1"></span>`;
    } else {
        heading.innerHTML = `DEPARTURES AS ON <span id="current-time"></span>`;
    }
}

// Set initial heading on page load
updateDepartureHeading(document.getElementById("date-selector").value);

function fetchLiveDepartures() {
    let selectedDate = $("#date-selector").val(); // Get selected date
    $.ajax({
        url: "database/fetch_live_departures.php",
        method: "GET",
        data: {
            date: selectedDate
        }, // Pass date as a parameter
        dataType: "json",
        success: function (response) {
            $("#current-time").text(response.time);
            $("#current-time1").text(response.time1);

            let tableContent = "";
            let lastDivision = "";
            let divisionData = {
                total_schedules: 0,
                actual_schedules: 0,
                difference: 0,
                late_departures: 0
            };
            let overallTotal = {
                total_schedules: 0,
                actual_schedules: 0,
                difference: 0,
                late_departures: 0
            };

            response.data.forEach((row, index) => {
                if (lastDivision && lastDivision !== row.division) {
                    tableContent += `<tr class="division-total">
                        <td colspan="2" class="clickable" onclick="opendepotschdetails('${lastDivision}', 'Division', '${selectedDate}')">${lastDivision} Total</td>
                        <td>${divisionData.total_schedules}</td>
                        <td>${divisionData.actual_schedules}</td>
                        <td class="clickable" onclick="openModal('${lastDivision}', 'difference', 'Division', '${selectedDate}')">${divisionData.difference}</td>
                        <td class="clickable" onclick="openModal('${lastDivision}', 'late', 'Division', '${selectedDate}')">${divisionData.late_departures}</td>
                        <td>${(divisionData.actual_schedules - divisionData.late_departures > 0) ? ((divisionData.actual_schedules - divisionData.late_departures) / divisionData.total_schedules * 100).toFixed(0) + '%' : '0%'}</td>
                    </tr>`;

                    // Reset division data
                    divisionData = {
                        total_schedules: 0,
                        actual_schedules: 0,
                        difference: 0,
                        late_departures: 0
                    };
                }

                tableContent += `<tr>
                    <td>${row.division}</td>
                    <td class="clickable" onclick="opendepotschdetails('${row.depot}', 'Depot', '${selectedDate}')">${row.depot}</td>
                    <td>${row.total_schedules}</td>
                    <td>${row.actual_schedules}</td>
                    <td class="clickable" onclick="openModal('${row.depot}', 'difference', 'Depot', '${selectedDate}')">${row.difference}</td>
                    <td class="clickable" onclick="openModal('${row.depot}', 'late', 'Depot', '${selectedDate}')">${row.late_departures}</td>
                    <td>${(row.actual_schedules - row.late_departures > 0) ? ((row.actual_schedules - row.late_departures) / row.total_schedules * 100).toFixed(0) + '%' : '0%'}</td>
                </tr>`;

                // Update division and overall totals
                divisionData.total_schedules += parseInt(row.total_schedules);
                divisionData.actual_schedules += parseInt(row.actual_schedules);
                divisionData.difference += parseInt(row.difference);
                divisionData.late_departures += parseInt(row.late_departures);

                overallTotal.total_schedules += parseInt(row.total_schedules);
                overallTotal.actual_schedules += parseInt(row.actual_schedules);
                overallTotal.difference += parseInt(row.difference);
                overallTotal.late_departures += parseInt(row.late_departures);

                lastDivision = row.division;
            });

            // Add last division total row
            tableContent += `<tr class="division-total">
                <td colspan="2" class="clickable" onclick="opendepotschdetails('${lastDivision}', 'Division', '${selectedDate}')">${lastDivision} Total</td>
                <td>${divisionData.total_schedules}</td>
                <td>${divisionData.actual_schedules}</td>
                <td class="clickable" onclick="openModal('${lastDivision}', 'difference', 'Division', '${selectedDate}')">${divisionData.difference}</td>
                <td class="clickable" onclick="openModal('${lastDivision}', 'late', 'Division', '${selectedDate}')">${divisionData.late_departures}</td>
                <td>${(divisionData.actual_schedules - divisionData.late_departures > 0) ? ((divisionData.actual_schedules - divisionData.late_departures) / divisionData.total_schedules * 100).toFixed(0) + '%' : '0%'}</td>
            </tr>`;

            // Update overall total row
            $("#overall-total-row").html(`<tr class="overall-total">
                <td colspan="2" class="clickable" onclick="opendepotschdetails('Corporation', 'Corporation', '${selectedDate}')">Corporation Total</td>
                <td>${overallTotal.total_schedules}</td>
                <td>${overallTotal.actual_schedules}</td>
                <td>${overallTotal.difference}</td>
                <td>${overallTotal.late_departures}</td>
                <td>${(overallTotal.actual_schedules - overallTotal.late_departures > 0) ? ((overallTotal.actual_schedules - overallTotal.late_departures) / overallTotal.total_schedules * 100).toFixed(0) + '%' : '0%'}</td>
            </tr>`);

            // Update report body
            $("#report-body").html(tableContent);
        }
    });
}


function openModal(id, type, location, selectedDate) {
    let modalId = type === 'difference' ? '#difference-modal' : '#late-modal';
    let modalBodyId = type === 'difference' ? '#difference-modal-body' : '#late-modal-body';

    // Convert selectedDate from yyyy-mm-dd to dd-mm-yyyy
    let formattedDate = selectedDate.split('-').reverse().join('-');

    $(modalId + " .modal-title").text(
        `${type === 'difference' ? "Difference" : "Late Departures"} Details for ${id} on ${formattedDate}`
    );
    $(modalBodyId).html("<tr><td colspan='8' class='text-center'>Loading...</td></tr>");
    $.ajax({
    url: "database/fetch_schedule_details.php",
    method: "POST",
    data: {
        id: id,
        type: type,
        location: location,
        date: selectedDate
    },
    dataType: "json",
    success: function (data) {
        let content = "";
        let header = ""; // ✅ define it once outside so it's accessible later

        // Get table header separately
        if (type === 'difference') {
            header = `
                <thead>
                    <tr>
                        <th>Sl.No</th>
                        ${location === 'Division' ? '<th>Depot</th>' : ''}
                        <th>Sch No</th>
                        <th class="desc-column">Description</th>
                        <th>Service Class</th>
                        <th>Sch Dep Time</th>
                    </tr>
                </thead>
            `;
        } else {
            header = `
                <thead>
                    <tr>
                        <th>Sl.No</th>
                        ${location === 'Division' ? '<th>Depot</th>' : ''}
                        <th>Sch No</th>
                        <th style="display:none;">Division</th>
                        <th style="display:none;">Depot</th>
                        <th class="desc-column">Description</th>
                        <th>Service Class</th>
                        <th>Sch Dep Time</th>
                        <th>Act Dep Time</th>
                        <th>Late By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
            `;
        }

        // Generate table body rows
        let body = "";

        if (!data || data.length === 0) {
            const colspan = location === 'Division' ? 6 : 5;
            body = `<tr><td colspan="${colspan}" class="text-center">No data available</td></tr>`;
        } else {
            body = data.map((row, index) => {
                if (type === 'difference') {
                    const depotColumn = location === 'Division'
                        ? `<td>${row.depot_name}</td>`
                        : '';
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            ${depotColumn}
                            <td>${row.sch_key_no}</td>
                            <td>${row.sch_abbr}</td>
                            <td>${row.name}</td>
                            <td>${row.sch_dep_time}</td>
                        </tr>
                    `;
                } else {
                    const depotColumn = location === 'Division'
                        ? `<td>${row.depot_name}</td>`
                        : '';
                    const lateTime = formatLateTime(row.late_by);
                    return `
                        <tr>
                            <td>${index + 1}</td>
                            ${depotColumn}
                            <td onclick="fetchScheduleDetails('${row.sch_key_no}', '${row.division_id}', '${row.depot_id}', '${row.sch_abbr}', '${row.name}', '${row.sch_dep_time}')">${row.sch_key_no}</td>
                            <td style="display:none;">${row.division_id}</td>
                            <td style="display:none;">${row.depot_id}</td>
                            <td>${row.sch_abbr}</td>
                            <td>${row.name}</td>
                            <td>${row.sch_dep_time}</td>
                            <td>${row.act_dep_time}</td>
                            <td>${lateTime}</td>
                            <td>${row.reason || 'N/A'}</td>
                        </tr>
                    `;
                }
            }).join("");
        }

        // Final table content (header + body)
        const table = `
            ${header}
            <tbody>
                ${body}
            </tbody>
        `;

        $(modalBodyId).html(table);
    }
});




    $(modalId).modal("show");
}

function fetchScheduleDetails(schNo, divisionId, depotId, description, serviceClass, schDepTime) {
    // Open modal immediately and show loading message
    $('#modal-sch-no').text(schNo);
    $('#modal-description').text(description);
    $('#modal-service-class').text(serviceClass);
    $('#modal-sch-dep-time').text(schDepTime);
    $('#schedule-details-modal').modal('show');
    $('#loading-message').show();
    $('#schedule-details-table').addClass('d-none');

    $.ajax({
        url: 'database/fetch_late_departure_details.php', // Your PHP script
        type: 'POST',
        data: {
            sch_no: schNo,
            division_id: divisionId,
            depot_id: depotId
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let html = '';

                response.data.forEach((row) => {
                    let dateParts = row.date.split(
                        '-'); // Assuming format is YYYY-MM-DD
                    let formattedDate =
                        `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                    let lateByText = formatLateBy(row.late_by);

                    html += `<tr>
                        <td>${formattedDate}</td>
                        <td>${row.dep_time}</td>
                        <td>${lateByText}</td>
                        <td>${row.driver_fixed == 0 ? '✔️' : '❌'}</td>
                        <td>${row.vehicle_fixed == 0 ? '✔️' : '❌'}</td>
                        <td>${row.reason || 'N/A'}</td>
                    </tr>`;
                });

                $('#schedule-details-modal-body').html(html);
                $('#loading-message').hide();
                $('#schedule-details-table').removeClass('d-none');
            } else {
                $('#schedule-details-modal-body').html(
                    '<tr><td colspan="5" class="text-center">No records found.</td></tr>'
                );
                $('#loading-message').hide();
                $('#schedule-details-table').removeClass('d-none');
            }
        },
        error: function () {
            alert('Error fetching schedule details.');
        }
    });
}

function formatLateTime(minutes) {
    let h = Math.floor(minutes / 60);
    let m = minutes % 60;
    return h > 0 ? `${h}h${m}m` : `${m}m`;
}

function formatLateBy(minutes) {
    let absMinutes = Math.abs(minutes);
    let hours = Math.floor(absMinutes / 60);
    let mins = absMinutes % 60;
    let formattedTime = `${hours}h ${mins}m`;

    if (minutes > 30) {
        return `<span class="late-time">${formattedTime}</span>`; // Late
    } else {
        return `On Time`;
    }
}

function opendepotschdetails(name, type) {
    let headerLabel = (type === "Depot") ?
        "Depot: " + name :
        (type === "Division") ?
            "Division: " + name :
            (type === "Corporation") ?
                "Corporation " :
                name;


    // Update the modal header and reset counts
    $("#modalDepotName").text(headerLabel);

    $("#modalScheduleCount").text("Loading...");
    $("#modalDepartureCount").text("Loading...");

    // Show the modal first with a loading message
    $("#scheduleModal .modal-body").html(
        "<p class='text-center'>Loading Schedules data, please wait...</p>");
    $("#scheduleModal").modal("show");

    $.ajax({
        url: "database/sch_live_fetch_all_schedule.php",
        type: "POST",
        data: {
            name: name,
            type: type
        },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                $("#scheduleModal .modal-body").html(response.html);

                // Update the modal header with schedule & departure counts
                $("#modalScheduleCount").text(response.schedule_count);
                $("#modalDepartureCount").text(response.departure_count);
            } else {
                $("#scheduleModal .modal-body").html(
                    "<p class='text-center text-danger'>No data found.</p>");
                $("#modalScheduleCount").text(0);
                $("#modalDepartureCount").text(0);
            }
        },
        error: function () {
            $("#scheduleModal .modal-body").html(
                "<p class='text-center text-danger'>Error fetching data.</p>");
            $("#modalScheduleCount").text(0);
            $("#modalDepartureCount").text(0);
        }
    });
}



setInterval(fetchLiveDepartures, 5000);
fetchLiveDepartures();
document.getElementById("date-selector").addEventListener("change", function () {
    fetchLiveDepartures();
});


$(document).ready(function () {
    function fetchOperationalStatistics() {
        var divisionId = $("#division").val();
        var depotId = $("#depot").val();

        if (divisionId !== "" && depotId !== "") {
            $.ajax({
                url: "includes/backend_data.php",
                type: "POST",
                data: {
                    action: "fetchLatestFile",
                    division: divisionId,
                    depot: depotId
                },
                dataType: "json", // Ensure the response is treated as JSON
                success: function (response) {
                    if (!response || response.file === "no_file") {
                        Swal.fire({
                            icon: "error",
                            title: "No File Found",
                            text: "No operational statistics file found for this selection."
                        });
                        // Reset division and depot selection
                        $("#division").val("");
                        $("#depot").html('<option value="">Depot</option>');
                        return;
                    }

                    let fileName = response.file.trim();
                    let reportDate = response.date || "";
                    let formattedDate = "";

                    if (reportDate) {
                        let dateParts = reportDate.split("-");
                        formattedDate =
                            `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                    }

                    let filePath = "../uploads/" + fileName;
                    let filePath2 = "uploads/" + fileName;
                    let googleDocsUrl =
                        "https://docs.google.com/gview?embedded=true&url=http://117.251.26.11:8880/" +
                        encodeURIComponent(filePath2);

                    let divisionText = $("#division option:selected").text();
                    let depotText = $("#depot option:selected").text();

                    $(".modal-title").text(
                        `Operational Statistics Details for Division: ${divisionText}, Depot: ${depotText} on Date: ${formattedDate}`
                    );

                    if (/Android|iPhone|iPad/i.test(navigator.userAgent)) {
                        $("#pdfViewer").attr("src", googleDocsUrl);
                    } else {
                        $("#pdfViewer").attr("src", filePath);
                    }

                    $("#downloadBtn").attr("href", filePath);
                    $("#operationalStatisticsMod").modal("show");
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Something went wrong. Please try again!"
                    });
                }
            });
        }
    }

    $("#division, #depot").change(fetchOperationalStatistics);

    $("#operationalStatisticsMod").on("hidden.bs.modal", function () {
        $("#division").val("");
        $("#depot").html('<option value="">Depot</option>');
        $("#pdfViewer").attr("src", "");
        $("#downloadBtn").attr("href", "#");
        $("#mobileWarning").hide();
    });
});

function fetchBusCategory() {
    $.ajax({
        url: 'includes/data_fetch.php',
        type: 'GET',
        data: {
            action: 'fetchDivision'
        },
        success: function (response) {
            var divisions = JSON.parse(response);
            $.each(divisions, function (index, division) {
                if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !==
                    'RWY') {
                    $('#division').append('<option value="' + division.division_id +
                        '">' +
                        division
                            .DIVISION + '</option>');
                }
            });
        }
    });

    $('#division').change(function () {
        var Division = $(this).val();
        $.ajax({
            url: 'includes/data_fetch.php?action=fetchDepot',
            method: 'POST',
            data: {
                division: Division
            },
            success: function (data) {
                // Update the depot dropdown with fetched data
                $('#depot').html(data);

                // Hide the option with text 'DIVISION'
                $('#depot option').each(function () {
                    if ($(this).text().trim() === 'DIVISION') {
                        $(this).hide();
                    }
                });
            }
        });
    });
}

function fetchBusCategory1() {
    $.ajax({
        url: 'includes/data_fetch.php',
        type: 'GET',
        data: {
            action: 'fetchDivision'
        },
        success: function (response) {
            var divisions = JSON.parse(response);
            $.each(divisions, function (index, division) {
                if (division.DIVISION !== 'HEAD-OFFICE' && division.DIVISION !==
                    'RWY') {
                    $('#division_id').append('<option value="' + division.division_id +
                        '">' +
                        division
                            .DIVISION + '</option>');
                }
            });
        }
    });

    $('#division_id').change(function () {
        var Division = $(this).val();
        $.ajax({
            url: 'includes/data_fetch.php?action=fetchDepot',
            method: 'POST',
            data: {
                division: Division
            },
            success: function (data) {
                // Update the depot dropdown with fetched data
                $('#depot_id').html(data);

                // Hide the option with text 'DIVISION'
                $('#depot_id').html('<option value="All">All</option>');
                $('#depot_id').append(data);
                $('#depot_id option').each(function () {
                    if ($(this).text().trim() === 'DIVISION' || $(this).text()
                        .trim() === 'KALABURAGI') {
                        $(this).hide();
                    }
                });
            }
        });
    });
}
$(document).ready(function () {
    fetchBusCategory();
    fetchBusCategory1();
});

$(document).ready(function () {
    $('#collapseTwo').on('show.bs.collapse', function () {
        fetchOffRoadData();
    });

    function fetchOffRoadData() {
        $("#loadingMessage").show();
        $("#offRoadDataTable").html(""); // Clear previous content

        $.ajax({
            url: "database/sch_live_fetch_offroad_data.php", // PHP script to fetch depot-wise data 
            type: "GET",
            dataType: "html",
            success: function (response) {
                $("#loadingMessage").hide();
                $("#offRoadDataTable").html(response);
            },
            error: function () {
                $("#loadingMessage").hide();
                $("#offRoadDataTable").html("<p>Error loading data.</p>");
            }
        });
    }
});

$(document).ready(function () {
    $('#collapseFour').on('show.bs.collapse', function () {
        fetchCommercialData();
    });

    function fetchCommercialData() {
        $("#loadingMessagecommerial").show();
        $("#commertialTable").html(""); // Clear previous content

        let urls = [
            "http://kkrtc.org/commercial/csms_live_fetch_stalls.php"
        ];

        let attempt = 0;

        function tryFetch() {
            if (attempt >= urls.length) {
                $("#loadingMessagecommerial").hide();
                $("#commertialTable").html("<p>Error loading data from kkrtc.org.</p>");
                return;
            }

            $.ajax({
                url: urls[attempt],
                type: "GET",
                dataType: "html",
                timeout: 100000, // Wait 100 seconds before failing
                success: function (response) {
                    $("#loadingMessagecommerial").hide();
                    $("#commertialTable").html(response);
                },
                error: function () {
                    attempt++;
                    tryFetch(); // Try the next URL
                }
            });
        }

        tryFetch(); // Start the first request
    }
});

function fetchoffroadDetails(id, name, type, subtype) {
    // Show modal with loading message
    $("#offroadModal .modal-body").html("<p class='text-center'>Loading...</p>");
    $("#offroadModal").modal("show");

    $.ajax({
        url: "database/sch_live_fetch_depot_offroad_data.php", // PHP script to fetch data
        type: "POST",
        data: {
            id: id,
            name: name,
            type: type,
            subtype: subtype
        },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                $("#offroadModal .modal-body").html(response.html);
            } else {
                $("#offroadModal .modal-body").html(
                    "<p class='text-center text-danger'>No off-road data found.</p>");
            }
        },
        error: function () {
            $("#offroadModal .modal-body").html(
                "<p class='text-center text-danger'>Error fetching data.</p>");
        }
    });
}
$(document).ready(function () {
    $('#collapseThree').on('show.bs.collapse', function () {
        fetchKMPLData();
    });

    function fetchKMPLData() {
        $("#loadingMessage1").show();
        $("#kmpl-content").html(""); // Clear previous content

        let selectedDate = $("#date-selector").val(); // Get selected date

        $.ajax({
            url: 'database/sch_live_fetch_kmpl_data.php', // PHP script to fetch KMPL data
            type: 'POST',
            data: {
                date: selectedDate // Send selected date
            },
            dataType: 'html',
            success: function (response) {
                $("#loadingMessage1").hide();
                $("#kmpl-content").html(response);
            },
            error: function () {
                $("#loadingMessage1").hide();
                $("#kmpl-content").html("<p>Error loading data.</p>");
            }
        });

    }
});

function fetchvehiclekmplDetails(id, type, selectedDate) {
    $.ajax({
        url: 'includes/backend_data.php', // Ensure the path is correct
        type: 'POST',
        data: {
            action: 'fetch_vehicle_kmpl',
            id: id,
            type: type,
            date: selectedDate
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                displayKMPLData(response.data, type, selectedDate);
            } else {
                console.error("Server Response Error:", response.message);
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error: ", status, error);
            console.error("Response Text:", xhr.responseText);
            alert('AJAX Request Failed: ' + error + '\nCheck the console for more details.');
        }
    });
}


async function fetchTokenNumber(pfNumber) {
    const apiUrl = `http://117.251.26.11:8880/dvp_test/database/combined_api_data.php?pf_no=${pfNumber}`;

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            console.error(`Error: HTTP status ${response.status}`);
            return "Other";
        }

        const data = await response.json();

        // Ensure 'data' contains an array and at least one item
        if (data && data.data && Array.isArray(data.data) && data.data.length > 0) {
            return data.data[0].token_number || "Other"; // Return token_number from first record
        } else {
            console.warn(`Token number not found for PF: ${pfNumber}`);
        }
    } catch (error) {
        console.error(`Error fetching token number for PF: ${pfNumber}`, error);
    }

    return "Other"; // Default value if not found or API fails
}


async function displayKMPLData(data, type, selectedDate) {
    // Show modal and set loading text
    $('#kmplDetailsBody').html('<p class="text-center">Loading data...</p>');
    $('#kmplDetailsModal').modal('show');

    // Check if data is available
    if (data.length === 0) {
        $('#kmplDetailsBody').html('<p class="text-center">No data available.</p>');
        return;
    }

    let tableHtml = `<table class="table table-bordered">
        <thead>
            <tr>
                <th>Sl. No</th>`;

    if (type === "Division") {
        tableHtml += `<th>Depot Name</th>`;
    }

    tableHtml += `<th>Bus Number</th>
                  <th>Route No</th>
                  <th>Crew Token</th>
                  <th>Km Operated</th>
                  <th>HSD</th>
                  <th>KMPL</th>
            </tr>
        </thead>
        <tbody>`;

    for (const [index, row] of data.entries()) {
        let crewTokens = [];

        if (row.driver_1_pf) {
            let token1 = await fetchTokenNumber(row.driver_1_pf);
            crewTokens.push(`${token1}`);
        }

        if (row.driver_2_pf) {
            let token2 = await fetchTokenNumber(row.driver_2_pf);
            crewTokens.push(`${token2}`);
        }

        let crewTokenString = crewTokens.length > 0 ? crewTokens.join(', ') : 'N/A';

        tableHtml += `<tr>
            <td>${index + 1}</td>`;

        if (type === "Division") {
            tableHtml += `<td>${row.depot_name}</td>`;
        }

        tableHtml += `<td>${row.bus_number}</td>
                      <td>${row.route_no}</td>
                      <td>${crewTokenString}</td>
                      <td>${row.km_operated}</td>
                      <td>${row.hsd}</td>
                      <td>${row.kmpl}</td>
                  </tr>`;
    }

    tableHtml += `</tbody></table>`;

    // Insert final table into modal body
    $('#kmplDetailsBody').html(tableHtml);
}

document.getElementById("bd-date-selector").addEventListener("change", function () {
    let selectedDate = this.value;
    let today = new Date().toISOString().split("T")[0];
    let yesterday = new Date(Date.now() - 864e5).toISOString().split("T")[0];

    if (selectedDate > yesterday) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Date Selection',
            text: 'You cannot select a future date!',
        });
        this.value = yesterday; // Reset to yesterday's date
        return;
    }

    updateBDHeading(selectedDate);
    fetchLiveBD();
});
function updateBDHeading(selectedDate) {
    let today = new Date().toISOString().split("T")[0];
    let heading = document.getElementById("bd-heading");

    heading.innerHTML = `Break Down AS ON <span id="current-datetime"></span>`;
}

// Set initial heading on page load
updateBDHeading(document.getElementById("bd-date-selector").value);

function fetchLiveBD() {
    let selectedDate = $("#bd-date-selector").val(); // Get selected date
    $("#loadingMessagebd").show();
    $("#bd-report-body").html("");
    $.ajax({
        url: "database/live_backend_data.php",
        method: "POST",
        data: {
            date: selectedDate,
            action: 'fetch_bd_data'
        },
        dataType: "json",
        success: function (response) {
            $("#loadingMessagebd").hide();
            $("#current-datetime").text(response.formatted_date);
            $("#bd-date-month").text(response.display_label);

            let tableContent = "";
            let lastDivision = "";
            let divisionData = {
                daily_bd_count: 0,
                monthly_bd_count: 0,
                yearly_bd_count: 0
            };
            let overallTotal = {
                daily_bd_count: 0,
                monthly_bd_count: 0,
                yearly_bd_count: 0
            };

            response.data.forEach((row, index) => {
                if (lastDivision && lastDivision !== row.division) {
                    tableContent += `<tr class="division-total">
                        <td colspan="2">${lastDivision} Total</td>
                        <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'dayBD', '${selectedDate}')">${divisionData.daily_bd_count}</td>
                        <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'monthBD', '${selectedDate}')">${divisionData.monthly_bd_count}</td>
                        <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'cummBD', '${selectedDate}')">${divisionData.yearly_bd_count}</td>
                    </tr>`;

                    divisionData = {
                        daily_bd_count: 0,
                        monthly_bd_count: 0,
                        yearly_bd_count: 0
                    };
                }

                tableContent += `<tr>
                    <td>${row.division}</td>
                    <td>${row.depot}</td>
                    <td class="clickable" onclick="opendepotBDDetails('${row.depot}', 'Depot', 'dayBD', '${selectedDate}')">${row.daily_bd_count}</td>
                    <td class="clickable" onclick="opendepotBDDetails('${row.depot}', 'Depot', 'monthBD', '${selectedDate}')">${row.monthly_bd_count}</td>
                    <td class="clickable" onclick="opendepotBDDetails('${row.depot}', 'Depot', 'cummBD', '${selectedDate}')">${row.yearly_bd_count}</td>
                </tr>`;

                divisionData.daily_bd_count += parseInt(row.daily_bd_count) || 0;
                divisionData.monthly_bd_count += parseInt(row.monthly_bd_count) || 0;
                divisionData.yearly_bd_count += parseInt(row.yearly_bd_count) || 0;

                overallTotal.daily_bd_count += parseInt(row.daily_bd_count) || 0;
                overallTotal.monthly_bd_count += parseInt(row.monthly_bd_count) || 0;
                overallTotal.yearly_bd_count += parseInt(row.yearly_bd_count) || 0;

                lastDivision = row.division;
            });

            // Add last division total row if data exists
            if (lastDivision) {
                tableContent += `<tr class="division-total">
                    <td colspan="2">${lastDivision} Total</td>
                    <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'dayBD', '${selectedDate}')">${divisionData.daily_bd_count}</td>
                    <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'monthBD', '${selectedDate}')">${divisionData.monthly_bd_count}</td>
                    <td class="clickable" onclick="opendepotBDDetails('${lastDivision}', 'Division', 'cummBD', '${selectedDate}')">${divisionData.yearly_bd_count}</td>
                </tr>`;
            }

            // Always render overall total row
            $("#bd-overall-total-row").html(`<tr class="overall-total">
                <td colspan="2">Corporation Total</td>
                <td class="clickable" onclick="opendepotBDDetails('Corporation', 'Overall', 'dayBD', '${selectedDate}')">${overallTotal.daily_bd_count}</td>
                <td class="clickable" onclick="opendepotBDDetails('Corporation', 'Overall', 'monthBD', '${selectedDate}')">${overallTotal.monthly_bd_count}</td>
                <td class="clickable" onclick="opendepotBDDetails('Corporation', 'Overall', 'cummBD', '${selectedDate}')">${overallTotal.yearly_bd_count}</td>
            </tr>`);

            $("#bd-report-body").html(tableContent);
        }
    });

    console.log("fetchLiveBD executed");
}

//setInterval(fetchLiveBD, 5000);
//fetchLiveBD();
document.getElementById("bd-date-selector").addEventListener("change", function () {
    fetchLiveBD();
});
$('#collapseSeven').on('show.bs.collapse', function () {
    fetchLiveBD();
});

function opendepotBDDetails(name, type, subtype, selectedDate) {
    let headerLabel = "Break Down Details - ";

    // Update the modal header and reset counts
    $("#modalBDDepotName").text(headerLabel);

    // Show the modal first with a loading message
    $("#BDModal .modal-body").html(
        "<p class='text-center'>Loading BD data, please wait...</p>");
    $("#BDModal").modal("show");
    console.log("Fetching BD details for", name, type, subtype, selectedDate);

    $.ajax({
        url: "database/live_backend_data.php",
        type: "POST",
        data: {
            name: name,
            type: type,
            subtype: subtype,
            date: selectedDate,
            action: 'fetch_bd_detailed_data'
        },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                $("#BDModal .modal-body").html(response.html);
            } else {
                $("#BDModal .modal-body").html(
                    "<p class='text-center text-danger'>No data found.</p>");
            }
        },
        error: function () {
            $("#BDModal .modal-body").html(
                "<p class='text-center text-danger'>Error fetching data.</p>");
        }
    });
}
$(document).ready(function () {

    // Load when accordion opens
    $('#collapseEight').on('show.bs.collapse', function () {
        fetchProgramData();
    });

    // Load when date changes
    $('#program-date-selector').on('change', function () {
        fetchProgramData();
    });

    function fetchProgramData() {
        $("#loadingMessageprogram").show();
        $("#ProgramTable").html("");

        let selectedDate = $("#program-date-selector").val(); // ✅ correct ID
        let action = "fetchprogramdata";

        $.ajax({
            url: 'database/live_backend_data.php',
            type: 'POST',
            data: {
                date: selectedDate,
                action: action
            },
            dataType: 'html',
            success: function (response) {
                $("#loadingMessageprogram").hide();
                $("#ProgramTable").html(response);
            },
            error: function () {
                $("#loadingMessageprogram").hide();
                $("#ProgramTable").html("<p>Error loading data.</p>");
            }
        });
    }

});
