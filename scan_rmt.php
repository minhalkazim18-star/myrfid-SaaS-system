<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';
require_active_account($conn, false);
$scanSchoolId = current_school_id();
$scanCsrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem RMT - Sila Scan</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-scan { 
            background: white; padding: 40px; border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 600px; 
        }
        #scan_status { font-weight: bold; font-size: 24px; margin-top: 20px; padding: 15px; border-radius: 10px; display: none; }
        .input-hidden { opacity: 0; position: absolute; } 
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card-scan">
                    
                    <h1 class="mb-4" style="font-weight: 800; color: #333;">REKOD RMT</h1>
                    <img src="images/touch.png" alt="Touch Here" style="width: 150px; margin-bottom: 30px; animation: pulse 2s infinite;">
                    
                    <h3 class="text-muted">SILA SENTUH KAD ANDA</h3>
                    
                    <div id="scan_status"></div>
                    <div id="student_info" style="font-size: 18px; margin-top: 10px; color: #555;"></div>

                    <input type="text" id="rfid_input" class="input-hidden" autocomplete="off" autofocus>
                    
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script>
    $(document).ready(function(){
        
        $('#rfid_input').focus();
        $('body').click(function(){ $('#rfid_input').focus(); });

        $('#rfid_input').on('change', function(){
            var rfid = $(this).val();
            
            $('#scan_status').show().html('Sedang Semak...').css({'background':'#eee', 'color':'#333'});

            $.ajax({
                url: "process_rmt.php",
                method: "POST",
                data: {rfid_uid: rfid, sch_id: <?php echo $scanSchoolId; ?>, csrf_token: <?php echo json_encode($scanCsrf); ?>},
                dataType: "json",
                success: function(resp){
                    
                    if(resp.status == 'success') {

                        playSound('success'); 
                        $('#scan_status').text('✅ ' + resp.msg).css({'background':'#d4edda', 'color':'#155724'});
                        $('#student_info').empty().append($('<strong>').text(resp.nama)).append('<br>').append(document.createTextNode(resp.kelas || ''));
                    
                    } else if(resp.status == 'duplicate') {

                        playSound('error');
                        $('#scan_status').text('⛔ ' + resp.msg).css({'background':'#f8d7da', 'color':'#721c24'});
                        $('#student_info').text('Nama: ' + (resp.nama || ''));
                    
                    } else {

                        $('#scan_status').text('⚠️ ' + resp.msg).css({'background':'#fff3cd', 'color':'#856404'});
                        $('#student_info').html('');
                    }

                    $('#rfid_input').val('');
                    
                    setTimeout(function(){
                        $('#scan_status').fadeOut();
                        $('#student_info').html('');
                    }, 3000);
                }
            });
        });

        function playSound(type) {

        }
    });
    </script>
</body>
</html>
