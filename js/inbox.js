function toggleDetails(button, jobId) {
    var row = button.closest('tr');
    var detailsRow = row.nextElementSibling;
    
    if (detailsRow.style.display === "none" || detailsRow.style.display === "") {
        detailsRow.style.display = "table-row";

        console.log("ส่งข้อมูลไปยัง update_status.php:", { job_id: jobId, status: 'อ่านแล้ว' });

        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId, status: 'อ่านแล้ว' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) console.log("อัปเดตสถานะสำเร็จ");
            else console.error("เกิดข้อผิดพลาด:", data.error);
        })
        .catch(error => console.error('Error:', error));

    } else {
        detailsRow.style.display = "none";
    }
}

function showFullDescription(fullDescription) {
    var words = fullDescription.split(' ');
    var formattedDescription = '';
    
    for (var i = 0; i < words.length; i += 10) {
        formattedDescription += words.slice(i, i + 10).join(' ') + '<br>';
    }
    
    document.getElementById('fullDescription').innerHTML = formattedDescription;
    document.getElementById('descriptionPopup').style.display = 'block';
}

function closePopup() {
    document.getElementById('descriptionPopup').style.display = 'none';
    var popup2 = document.getElementById('descriptionPopup2');
    if (popup2) popup2.style.display = 'none';
}

function uploadFile(event) {
    event.preventDefault();
    
    var form = document.getElementById("uploadForm");
    var formData = new FormData(form);
    
    if (!formData.has('fileUpload')) {
        alert('กรุณาเลือกไฟล์');
        return;
    }
    
    if (formData.get('reply_description').trim() === '') {
        alert('กรุณากรอกรายละเอียดงาน');
        return;
    }

    fetch('reply_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire(data.message);
        if (data.message === 'อัปโหลดไฟล์และบันทึกการตอบกลับเสร็จสมบูรณ์') {
            closePopup();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
    });
}

function updateSortOrder() {
    const sortOrder = document.getElementById('sortOrder').value;
    if (sortOrder) {
        window.location.href = `?sort=${sortOrder}`;
    }
}

function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("jobTable");
    var rows = table.getElementsByTagName("tr");

    for (var i = 1; i < rows.length; i++) {
        var cells = rows[i].getElementsByTagName("td");
        var match = false;
        
        for (var j = 0; j < cells.length; j++) {
            if (cells[j]) {
                var textValue = cells[j].textContent || cells[j].innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    match = true;
                    break;
                }
            }
        }
        rows[i].style.display = match ? "" : "none";
    }
}

function checkEnter(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        searchTable();
    }
}
/*////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

        // ฟังก์ชันแสดง Popup
        function showPopup(jobId) {
            // ตั้งค่า job_id ให้กับ input hidden
            document.getElementById('jobId').value = jobId;

            // นำข้อมูลที่ต้องการแสดงใน Popup มาตั้งค่า
            document.getElementById('fullDescription').innerHTML = 'กำลังส่งงานที่ ID: ' + jobId; // หรือข้อมูลที่ต้องการจากฐานข้อมูล
            document.getElementById('descriptionPopup').style.display = 'block'; // เปิด Popup
        }

        // ฟังก์ชันปิด Popup
        function closePopup() {
            document.getElementById('descriptionPopup').style.display = 'none'; // ปิด Popup
        }
        // ฟังก์ชันในการอัปโหลดไฟล์โดยไม่รีเฟรชหน้า
        function uploadFile(event) {
            event.preventDefault(); // ป้องกันการรีเฟรชหน้าจากการส่งฟอร์ม

            // สร้าง FormData object
            var formData = new FormData(document.getElementById('uploadForm'));

            // ใช้ XMLHttpRequest (AJAX) ส่งข้อมูล
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'reply_upload.php', true);

            xhr.onload = function() {
                if (xhr.status == 200) {
                    // แสดงผลลัพธ์จาก PHP (ตอบกลับในรูปแบบ JSON)
                    var response = JSON.parse(xhr.responseText);
                    alert(response.message); // แสดงข้อความที่ตอบกลับจาก server

                    // ปิด Popup หลังจากการอัปโหลดสำเร็จ
                    closePopup();
                } else {
                    alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์!');
                }
            };

            // ส่งข้อมูลไปยัง server
            xhr.send(formData);
        }






        // ฟังก์ชันเพื่อแสดงรายละเอียดงานทั้งหมดใน popup
        function showFullDescription(fullDescription) {
            // แบ่งคำในรายละเอียดงาน
            var words = fullDescription.split(' ');
            var formattedDescription = '';

            // กำหนดให้แต่ละบรรทัดมี 10 คำ
            for (var i = 0; i < words.length; i += 10) {
                formattedDescription += words.slice(i, i + 10).join(' ') + '\n'; // ใช้ \n เพื่อเว้นบรรทัด
            }

            // แสดงรายละเอียดทั้งหมดใน popup
            document.getElementById('fullDescription').textContent = fullDescription;
            document.getElementById('descriptionPopup').style.display = 'block'; // เปิด popup
        }

        // ฟังก์ชันเพื่อปิด popup
        function closePopup() {
            document.getElementById('descriptionPopup').style.display = 'none'; // ปิด popup
        }

        function toggleDetails(button, jobId) {
            var row = button.closest('tr');
            var detailsRow = row.nextElementSibling;

            if (detailsRow.style.display === "none" || detailsRow.style.display === "") {
                detailsRow.style.display = "table-row";

                // แสดงข้อมูลที่ถูกส่ง
                console.log("ส่งข้อมูลไปยัง update_status.php:", {
                    job_id: jobId,
                    status: 'อ่านแล้ว'
                });

                // ส่งคำขอ AJAX
                fetch('update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: jobId, status: 'อ่านแล้ว' })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Parsed JSON:", data);
                    if (data.success) {
                        console.log("✅ อัปเดตสถานะสำเร็จ");
                    } else if (data.message.includes("ไม่มีการเปลี่ยนแปลง")) {
                        console.warn("ℹ️ สถานะเป็น 'อ่านแล้ว' อยู่แล้ว");
                    } else {
                        console.error("❌ เกิดข้อผิดพลาด:", data.error || data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
                

            } else {
                detailsRow.style.display = "none";
            }
        }



        // ฟังก์ชันสำหรับการเรียงลำดับงาน
        function updateSortOrder() {
            const sortOrder = document.getElementById('sortOrder').value;
            if (sortOrder) {
                window.location.href = `?sort=${sortOrder}`; // เปลี่ยน URL ตามค่าที่เลือก
            }
        }

        function searchTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase(); // ทำให้เป็นตัวอักษรพิมพ์ใหญ่
            var table = document.getElementById("jobTable"); // ตัวอย่าง table ID
            var rows = table.getElementsByTagName("tr"); // หาตัวแถวทั้งหมดในตาราง

            // ลูปผ่านทุกแถวในตาราง (เริ่มจากแถวที่สองเพื่อข้ามส่วนหัว)
            for (var i = 1; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName("td"); // หาค่าของแต่ละเซลล์ในแถว

                var match = false;
                // ลูปผ่านทุกเซลล์ในแถว
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        var textValue = cells[j].textContent || cells[j].innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }

                // แสดงหรือซ่อนแถวตามผลการค้นหา
                if (match) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function checkEnter(event) {
            if (event.key === "Enter") { // ตรวจสอบว่าเป็นการกดปุ่ม Enter
                event.preventDefault(); // ป้องกันการส่งฟอร์มหรือการทำงานอื่น ๆ
                searchTable(); // เรียกใช้ฟังก์ชัน searchTable
            }
        }
