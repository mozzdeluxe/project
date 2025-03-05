
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
