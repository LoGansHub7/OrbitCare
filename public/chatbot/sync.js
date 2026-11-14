(function (root) {
    root.OrbitSync = async function (baseUrl) {
        const sessionResponse = await fetch(new URL('chatbot/session', baseUrl), {
            credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' },
        });
        if (!sessionResponse.ok) throw new Error(`Session check failed (${sessionResponse.status}).`);
        const session = await sessionResponse.json();
        const queued = await root.OrbitStore.queue(session.astronaut_id);
        for (let offset = 0; offset < queued.length; offset += 50) {
            const messages = queued.slice(offset, offset + 50);
            const response = await fetch(new URL('chatbot/messages/sync', baseUrl), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': session.csrf_token,
                },
                body: JSON.stringify({ astronaut_id: session.astronaut_id, messages }),
            });
            if (!response.ok) throw new Error(`Message sync failed (${response.status}).`);
            const result = await response.json();
            await root.OrbitStore.removeQueued(result.synced);
        }
    };
})(globalThis);
