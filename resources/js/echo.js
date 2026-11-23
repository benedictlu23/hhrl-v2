import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (!window.Echo) {
    const host = window.location.hostname || '127.0.0.1';
    const port = Number(import.meta.env.VITE_REVERB_PORT || 8080);
    const isHttps = window.location.protocol === 'https:';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'k2no9pahk0j0rb6p6bha',
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
    });
}

