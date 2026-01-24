 </div>
 </div>
 </div>


 <script>
     function handleUserTypeChange() {
         const userType = document.getElementById('usertype');

         if (userType.value !== '') {
             document.getElementById('userTypeForm').submit();
         }
         console.log(userType.value);

         if (userType.value === 'central') {
             window.location.href = '../pages/index.php';
         } else if (userType.value === 'admin') {
             window.location.href = '../admin/admin_pannel.php';
         } else {
             alert('Please select a user type');
         }
     }
 </script>


<br><br>
<footer id="sticky-footer"
  style="position: fixed; bottom: 0; left: 0; width: 100%; background: linear-gradient(90deg, #3c97bf,  #3c97bf, #3c97bf); color: white; text-align: center; padding: 10px 0; font-size: 14px; z-index: 1000;">
  <div>
    <span>© Copyright 2024 KKRTC | All Rights Reserved</span>
  </div>
</footer>
</div>
</div>
<script>
  


  function checkSession() {
    fetch('../pages/session1.php') // Calls the session checker
      .then(response => response.json())
      .then(data => {
        if (data.status === 'expired') {
          alert("Session expired or missing necessary information. You will be logged out. Please Login Again.");
          window.location.href = '../pages/logout.php';
        }
      })
      .catch(error => console.error('Session check error:', error));
  }

  // Check session every second
  setInterval(checkSession, 60000);
</script>

<!-- End of Footer -->
<!-- Scroll to Top Button-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" crossorigin="anonymous"></script>
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>


<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8"
  src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<!-- DataTable initialization -->
<script>
  $(document).ready(function() {
    $('#dataTable').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true // Automatically adjust column widths
    });
    $('#dataTable1').DataTable({
      "paging": true, // Enable pagination
      "lengthChange": true, // Enable the row count dropdown
      "searching": true, // Enable search functionality
      "ordering": true, // Enable sorting
      "info": true, // Show table information summary
      "autoWidth": true // Automatically adjust column widths
    });
  });

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>




</body>

</html>
