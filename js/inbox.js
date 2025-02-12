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
        .then(response => {
            if (!response.ok) throw new Error('เกิดข้อผิดพลาดจากเซิร์ฟเวอร์');
            return response.json();
        })
        .then(data => {
            if (data.success) console.log("อัปเดตสถานะสำเร็จ");
            else console.error("เกิดข้อผิดพลาด:", data.error);
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

////
// ฟังก์ชันปิด Popup
function closePopup() {
    document.getElementById('descriptionPopup').style.display = 'none'; // ปิด Popup
}

// ฟังก์ชันแสดง Popup
function showPopup(jobId) {
    // ตั้งค่า job_id ให้กับ input hidden
    document.getElementById('jobId').value = jobId;

    // นำข้อมูลที่ต้องการแสดงใน Popup มาตั้งค่า
    document.getElementById('fullDescription').innerHTML = 'กำลังส่งงานที่ ID: ' + jobId; // หรือข้อมูลที่ต้องการจากฐานข้อมูล
    document.getElementById('descriptionPopup').style.display = 'block'; // เปิด Popup
}

// ฟังก์ชันในการอัปโหลดไฟล์โดยไม่รีเฟรชหน้า
function uploadFile(event) {
    event.preventDefault(); // ป้องกันการรีเฟรชหน้า

    let form = document.getElementById("uploadForm");
    let formData = new FormData(form);

    // ตรวจสอบค่าก่อนส่งไปยังเซิร์ฟเวอร์
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    // ตรวจสอบว่าไฟล์ถูกเลือกหรือไม่
    if (!formData.has('fileUpload')) {
        alert('กรุณาเลือกไฟล์');
        return;
    }

    // ตรวจสอบว่าได้กรอกคำอธิบายหรือไม่
    if (formData.get('reply_description').trim() === '') {
        alert('กรุณากรอกรายละเอียดงาน');
        return;
    }

    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('reply_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        Swal.fire(data.message); // แสดงข้อความจากเซิร์ฟเวอร์

        // หากส่งงานสำเร็จ ให้ปิด Popup
        if (data.message === 'อัปโหลดไฟล์และบันทึกการตอบกลับเสร็จสมบูรณ์') {
            closePopup(); // ปิด Popup หลังจากการอัปโหลดสำเร็จ
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
    });
}

// ฟังก์ชันในการแสดงรายละเอียดงานทั้งหมดใน popup
function showFullDescription(fullDescription) {
    let formattedDescription = fullDescription.split(' ').map((word, index) => {
        return (index + 1) % 10 === 0 ? word + '<br>' : word;
    }).join(' ');

    document.getElementById('fullDescription').innerHTML = formattedDescription;
    document.getElementById('descriptionPopup').style.display = 'block';
}
