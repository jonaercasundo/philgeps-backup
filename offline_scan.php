<?php
$id = $_GET['id'] ?? null;
$delivery_id = $_GET['delivery_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Offline Delivery</title>
</head>
<body>
  <h2>Offline Delivery</h2>
  <p>Package ID: <span id="pkg"><?= htmlspecialchars($id) ?></span></p>
  <p>Delivery ID: <span id="del"><?= htmlspecialchars($delivery_id) ?></span></p>

  <form id="offlineForm">
    <input type="hidden" id="order_id" value="<?= htmlspecialchars($id) ?>">
    <input type="hidden" id="delivery_id" value="<?= htmlspecialchars($delivery_id) ?>">
    <input type="file" id="photo" accept="image/*" capture="camera" required>
    <button type="submit">Save Offline</button>
  </form>

  <script>
async function saveOffline(file) {
  const db = await openDB("deliveryDB", 1);
  const tx = db.transaction("uploads", "readwrite");
  const store = tx.objectStore("uploads");

  await store.add({
    idVal: "<?= $id ?>",
    delivery_id: "<?= $delivery_id ?>",
    filename: file.name,
    file: file,
    status: "pending",
    savedAt: new Date().toISOString()
  });

  // Register background sync
  navigator.serviceWorker.ready.then((reg) => {
    reg.sync.register("sync-uploads");
  });

  alert("📦 Saved offline. Will auto-upload when online.");
}

function openDB(name, version) {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(name, version);
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains("uploads")) {
        db.createObjectStore("uploads", { keyPath: "id", autoIncrement: true });
      }
    };
    req.onsuccess = (e) => resolve(e.target.result);
    req.onerror = (e) => reject(e.target.error);
  });
}

document.getElementById("offlineForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const file = document.getElementById("photo").files[0];
  if (file) await saveOffline(file);
});
</script>

</body>
</html>
