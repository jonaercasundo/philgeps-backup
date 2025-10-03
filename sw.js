self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open("delivery-cache").then((cache) => {
      return cache.addAll([
        "/philgeps/entry.php",
        "/philgeps/offline_scan.php",
        "/philgeps/success.php"
      ]);
    })
  );
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request, { ignoreSearch: true }))
  );
});

// Background Sync trigger
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-uploads") {
    event.waitUntil(syncUploads());
  }
});

// ---- IndexedDB Helper ----
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

// ---- Sync Uploads ----
async function syncUploads() {
  const db = await openDB("deliveryDB", 1);
  const tx = db.transaction("uploads", "readwrite");
  const store = tx.objectStore("uploads");
  const all = await store.getAll();

  for (let upload of all) {
    const fd = new FormData();
    fd.append("id", upload.idVal);
    fd.append("delivery_id", upload.delivery_id);
    fd.append("status", upload.status || "pending");
    fd.append("photo_upload[]", upload.file, upload.filename);

    try {
      const res = await fetch("/philgeps/check.php", {
        method: "POST",
        body: fd
      });

      if (res.ok) {
        store.delete(upload.id); // remove after successful sync
        console.log("✅ Synced", upload.filename);
      } else {
        console.warn("❌ Failed sync, keep for retry:", upload.filename);
      }
    } catch (e) {
      console.warn("⚠ Retry later for", upload.filename, e);
    }
  }
}
