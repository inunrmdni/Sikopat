<?php
include '../koneksi.php';
$q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM notifikasi WHERE status='baru'");
$d = mysqli_fetch_assoc($q);
echo json_encode(['total' => $d['total']]);
?>
<script>
setInterval(() => {
  fetch('cek_notifikasi.php')
    .then(res => res.json())
    .then(data => {
      const badge = document.querySelector('#notifDropdown .badge');
      if (badge) badge.remove();
      if (data.total > 0) {
        const newBadge = document.createElement('span');
        newBadge.className = 'badge';
        newBadge.textContent = data.total;
        document.querySelector('#notifDropdown').appendChild(newBadge);
      }
    });
}, 10000);
</script>
