(function (root) {
    const database = new Promise((resolve, reject) => {
        const request = indexedDB.open('orbitcare-assistant', 1);
        request.onupgradeneeded = () => {
            const db = request.result;
            db.createObjectStore('history', { keyPath: 'client_id' }).createIndex('owner', 'astronaut_id');
            db.createObjectStore('queue', { keyPath: 'client_id' }).createIndex('owner', 'astronaut_id');
            db.createObjectStore('snapshots', { keyPath: 'astronaut_id' });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
        request.onblocked = () => reject(new Error('Offline storage upgrade is blocked by another tab.'));
    });

    async function run(storeNames, mode, action) {
        const db = await database;
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeNames, mode);
            let result;
            try {
                result = action(tx);
            } catch (error) {
                tx.abort();
                reject(error);
                return;
            }
            tx.oncomplete = () => resolve(result?.result);
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(tx.error || new Error('Offline storage transaction aborted.'));
        });
    }

    root.OrbitStore = {
        saveMessage(message) {
            return run(['history', 'queue'], 'readwrite', (tx) => {
                tx.objectStore('history').put(message);
                tx.objectStore('queue').put(message);
            });
        },
        // Clears this device's copy only; messages already synced stay in MySQL.
        clearHistory(owner) {
            return run(['history', 'queue'], 'readwrite', (tx) => {
                ['history', 'queue'].forEach((name) => {
                    const store = tx.objectStore(name);
                    store.index('owner').getAllKeys(owner).onsuccess = (event) => event.target.result.forEach((key) => store.delete(key));
                });
            });
        },
        history(owner) {
            return run(['history'], 'readonly', (tx) => tx.objectStore('history').index('owner').getAll(owner));
        },
        queue(owner) {
            return run(['queue'], 'readonly', (tx) => tx.objectStore('queue').index('owner').getAll(owner));
        },
        removeQueued(ids) {
            return run(['queue'], 'readwrite', (tx) => ids.forEach((id) => tx.objectStore('queue').delete(id)));
        },
        saveSnapshot(snapshot) {
            return run(['snapshots'], 'readwrite', (tx) => tx.objectStore('snapshots').put(snapshot));
        },
        snapshot(owner) {
            return run(['snapshots'], 'readonly', (tx) => tx.objectStore('snapshots').get(owner));
        },
    };
})(globalThis);
