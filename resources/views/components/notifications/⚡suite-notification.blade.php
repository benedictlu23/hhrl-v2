<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use App\Models\Suite\RoomBooking;

new class extends Component
{
    #[On('echo:bookings,.NewBookingReceived')]
    #[On('echo:bookings,.BookingCancelled')]
    #[On('echo:bookings,.CheckInUpdated')]
    #[On('echo-private:bookings,.NewBookingReceived')]
    #[On('echo-private:bookings,.BookingCancelled')]
    #[On('echo-private:bookings,.CheckInUpdated')]
    public function handleRealtimeUpdate($event = null): void
    {
        // Re-renders and re-evaluates pendingCount & notifications in real time
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function openNotification(string $notificationId)
    {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
                $url = $notification->data['url'] ?? route('room-booking.list');
                return $this->redirect($url, navigate: true);
            }
        }

        return $this->redirect(route('room-booking.list'), navigate: true);
    }

    public function with(): array
    {
        $user = Auth::user();

        // Badge counter strictly reflects pending approvals: only decrements when approved or declined
        $pendingCount = RoomBooking::query()
            ->where('status', 'pending')
            ->count();

        $notifications = $user ? $user->notifications()->latest()->take(15)->get() : collect();

        try {
            $recentBookingsSeed = RoomBooking::with('room')
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'booking_code' => $b->booking_code,
                        'guest_name' => $b->guest_name,
                        'room_number' => $b->room?->room_number ?? 'Suite',
                        'total_price' => (float) $b->total_price,
                        'status' => $b->status,
                        'created_at' => $b->created_at?->diffForHumans() ?? 'Recently',
                        'timestamp' => $b->created_at?->timestamp * 1000 ?? now()->timestamp * 1000,
                        'type' => 'booking',
                        'title' => 'Reservation Folio',
                        'message' => "{$b->guest_name} reserved " . ($b->room?->room_number ? 'Room ' . $b->room->room_number : 'Room') . " — {$b->booking_code}",
                        'read' => true,
                    ];
                });
        } catch (\Throwable) {
            $recentBookingsSeed = collect();
        }

        return [
            'pendingCount'       => $pendingCount,
            'notifications'      => $notifications,
            'recentBookingsSeed' => $recentBookingsSeed,
        ];
    }
};
?>

<div class="relative z-50"
     x-data="bookingNotifier()"
     @click.outside="menuOpen = false"
     @keydown.escape.window="menuOpen = false"
     wire:key="navbar-suite-notifications">
    {{-- Real-time notification toast --}}
    <div id="booking-toast" class="fixed top-5 right-5 z-[9999] space-y-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible" x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto max-w-sm bg-white/95 backdrop-blur-md border border-[#233D32]/15 rounded-2xl shadow-[0_12px_40px_rgba(23,42,34,0.15)] p-4 flex items-start gap-3.5">
                <div class="shrink-0 w-9 h-9 bg-[#FAF8F4] border border-[#233D32]/10 rounded-xl flex items-center justify-center text-[#233D32]">
                    <svg class="w-4 h-4 text-[#A68B61]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-serif font-bold text-[#233D32] text-sm" x-text="toast.title"></p>
                    <p class="text-xs text-[#777A72] mt-0.5 font-light leading-relaxed" x-text="toast.message"></p>
                </div>
                <button @click="toast.visible = false" class="shrink-0 text-[#777A72] hover:text-[#233D32] p-1 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Suite Booking Notification Trigger Button --}}
    <button @click="menuOpen = !menuOpen; if(menuOpen && unreadCount > 0) markAllAsRead()"
            type="button"
            class="relative p-1.5 sm:p-2 rounded-full cursor-pointer duration-200 transition-colors
                   text-primary-600 hover:text-primary-700 hover:bg-primary-50
                   dark:text-primary-50 dark:hover:text-primary-600 dark:hover:bg-white/10
                   focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            :class="{ 'bg-primary-50 dark:bg-white/10 ring-2 ring-primary-500/30': menuOpen }"
            aria-label="Real-time Suite Booking Notifications"
            title="Real-time Suite Booking Notifications (Reverb)">
        
        <span class="inline-flex transition-transform" :class="{ 'animate-bounce text-primary-700': isRinging }">
            <x-icon-luggage class="w-7 h-7 sm:w-8 sm:h-8" />
        </span>

        {{-- Dynamic Badge Counter --}}
        <template x-if="unreadCount > 0">
            <span class="absolute top-0.5 right-0.5 sm:top-1 sm:right-1 flex items-center justify-center min-w-4.5 h-4.5 sm:min-w-5 sm:h-5 px-1
                         text-[9px] sm:text-[10px] font-bold text-white bg-red-500 rounded-full 
                         dark:border-gray-800 animate-pulse shadow-sm"
                  x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
        </template>
    </button>

                            {{-- Dropdown Notification Panel --}}
                            <div x-show="menuOpen"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2.5 w-80 sm:w-96 bg-white/95 backdrop-blur-md border border-[#233D32]/15 rounded-2xl shadow-[0_16px_40px_rgba(23,42,34,0.18)] z-50 overflow-hidden"
                                 style="display: none;">
                                
                                {{-- Dropdown Header --}}
                                <div class="px-4 py-3 bg-[#FAF8F4] border-b border-[#233D32]/10 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-serif font-bold text-sm text-[#233D32]">Booking Alerts</span>
                                        {{-- Reverb Live Status Indicator --}}
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
                                              :class="reverbConnected ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/80' : 'bg-amber-50 text-amber-800 border border-amber-200/80'">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                  :class="reverbConnected ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                                            <span x-text="reverbConnected ? 'Reverb Live' : 'Reverb Syncing'"></span>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        {{-- Sound Chime Toggle --}}
                                        <button type="button" @click="toggleSound()"
                                                :title="soundEnabled ? 'Chime sound: Enabled (Click to mute)' : 'Chime sound: Muted (Click to enable)'"
                                                class="p-1.5 rounded-lg text-stone-500 hover:text-[#233D32] hover:bg-stone-100 transition-colors"
                                                aria-label="Toggle chime sound">
                                            <template x-if="soundEnabled">
                                                <svg class="w-3.5 h-3.5 text-[#233D32]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                                </svg>
                                            </template>
                                            <template x-if="!soundEnabled">
                                                <svg class="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                                </svg>
                                            </template>
                                        </button>

                                        {{-- Clear list button --}}
                                        <template x-if="notifications.length > 0">
                                            <button type="button" @click="clearNotifications()"
                                                    class="text-[11px] text-stone-500 hover:text-rose-600 px-1.5 py-0.5 rounded transition-colors"
                                                    title="Clear notification list">
                                                Clear
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                {{-- Optional Desktop Notification Permission Request Banner --}}
                                <template x-if="desktopPermissionNeeded">
                                    <div class="px-4 py-2 bg-[#EFEBE1]/80 border-b border-[#233D32]/10 flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-[#233D32]">Enable desktop alerts for incoming bookings?</span>
                                        <button type="button" @click="requestDesktopPermission()"
                                                class="px-2 py-0.5 bg-[#233D32] text-white text-[10px] font-semibold rounded-md hover:bg-[#172A22] transition-colors">
                                            Enable
                                        </button>
                                    </div>
                                </template>

                                {{-- Notifications Scrollable Body --}}
                                <div class="max-h-80 sm:max-h-96 overflow-y-auto divide-y divide-stone-100">
                                    <template x-if="notifications.length === 0">
                                        <div class="p-8 text-center">
                                            <div class="w-10 h-10 rounded-full bg-[#FAF8F4] border border-[#233D32]/10 mx-auto flex items-center justify-center text-[#A68B61] mb-2.5">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                            </div>
                                            <p class="font-serif font-bold text-sm text-[#233D32]">All caught up!</p>
                                            <p class="text-xs text-stone-500 mt-1 font-light leading-relaxed">
                                                Listening in real-time via Reverb. New guest reservations will appear here immediately.
                                            </p>
                                        </div>
                                    </template>

                                    <template x-for="(item, index) in notifications" :key="item.timestamp + '-' + index">
                                        <a :href="item.id ? ('/bookings/show/' + item.id) : '{{ route('room-booking.list') }}'"
                                           @click="item.read = true; saveToStorage()"
                                           class="p-3.5 flex items-start gap-3 hover:bg-[#FAF8F4]/80 transition-colors group block relative"
                                           :class="{ 'bg-[#FAF8F4]/40': !item.read }">
                                            
                                            {{-- Unread Dot --}}
                                            <template x-if="!item.read">
                                                <span class="absolute left-1.5 top-5 w-1.5 h-1.5 rounded-full bg-[#A68B61]"></span>
                                            </template>

                                            {{-- Icon by Notification Type --}}
                                            <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center shadow-2xs mt-0.5"
                                                 :class="{
                                                    'bg-emerald-50 text-emerald-700 border border-emerald-200/80': item.type === 'booking',
                                                    'bg-rose-50 text-rose-700 border border-rose-200/80': item.type === 'cancelled',
                                                    'bg-sky-50 text-sky-700 border border-sky-200/80': item.type === 'checkin'
                                                 }">
                                                <template x-if="item.type === 'booking'">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </template>
                                                <template x-if="item.type === 'cancelled'">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </template>
                                                <template x-if="item.type === 'checkin'">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </template>
                                            </div>

                                            {{-- Content Info --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-1">
                                                    <p class="text-xs font-semibold text-[#233D32] truncate" x-text="item.guest_name || 'Guest'"></p>
                                                    <span class="text-[10px] text-stone-400 shrink-0" x-text="item.created_at"></span>
                                                </div>
                                                <p class="text-xs text-stone-600 font-light mt-0.5 line-clamp-2 leading-relaxed" x-text="item.message"></p>
                                                
                                                <div class="flex items-center gap-2 mt-1.5 text-[10px]">
                                                    <span class="font-mono px-1.5 py-0.5 bg-stone-100 rounded text-stone-700" x-text="item.booking_code"></span>
                                                    <template x-if="item.total_price">
                                                        <span class="font-semibold text-[#A68B61]" x-text="'₱' + Number(item.total_price).toLocaleString()"></span>
                                                    </template>
                                                    <span class="text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded font-semibold ml-auto"
                                                          :class="{
                                                              'bg-amber-100 text-amber-800': item.status === 'pending',
                                                              'bg-emerald-100 text-emerald-800': item.status === 'confirmed' || item.status === 'checked_in',
                                                              'bg-rose-100 text-rose-800': item.status === 'cancelled'
                                                          }"
                                                          x-text="item.status"></span>
                                                </div>
                                            </div>
                                        </a>
                                    </template>
                                </div>

                                {{-- Dropdown Footer --}}
                                <div class="p-2.5 bg-[#FAF8F4] border-t border-[#233D32]/10 text-center">
                                    <a href="{{ route('room-booking.list') }}"
                                       class="text-xs font-semibold text-[#233D32] hover:text-[#172A22] uppercase tracking-wider block py-1 hover:underline">
                                        View All Folio Reservations →
                                    </a>
                                </div>
                            </div>

        <script>
            window._initialBookingSeeds = @json($recentBookingsSeed);
            function bookingNotifier() {
                return {
                    menuOpen: false,
                    notifications: [],
                    unreadCount: 0,
                    toasts: [],
                    soundEnabled: true,
                    reverbConnected: false,
                    desktopPermissionNeeded: false,
                    isRinging: false,

                    init() {
                        // Desktop notification permission check
                        if ('Notification' in window) {
                            this.desktopPermissionNeeded = (Notification.permission === 'default');
                        }

                        // Sound preference from localStorage
                        const savedSound = localStorage.getItem('agro_admin_notif_sound');
                        if (savedSound !== null) {
                            this.soundEnabled = (savedSound === 'true');
                        }

                        // Load cached notifications from localStorage
                        const cached = localStorage.getItem('agro_admin_notifications');
                        if (cached) {
                            try {
                                this.notifications = JSON.parse(cached);
                            } catch(e) {
                                this.notifications = [];
                            }
                        } else {
                            // Seed initial recent records
                            this.notifications = Array.isArray(window._initialBookingSeeds)
                                ? window._initialBookingSeeds.slice(0, 6)
                                : [];
                        }

                        this.updateUnreadCount();

                        // Initialize Echo Reverb listener
                        this.setupEcho();
                    },

                    setupEcho() {
                        if (typeof window.Echo === 'undefined') {
                            setTimeout(() => this.setupEcho(), 200);
                            return;
                        }

                        // Register this component instance for global Reverb events
                        window._currentBookingNotifierInstance = this;

                        // Track Reverb connection status
                        if (window.Echo.connector && window.Echo.connector.pusher) {
                            const pusher = window.Echo.connector.pusher;

                            const updateConnectionState = () => {
                                const isConnected = (pusher.connection.state === 'connected');
                                if (this.reverbConnected !== isConnected) {
                                    this.reverbConnected = isConnected;
                                }
                            };

                            updateConnectionState();

                            pusher.connection.bind('connected', () => {
                                updateConnectionState();
                                console.log('[Reverb] Connected to WebSocket server');
                            });
                            pusher.connection.bind('unavailable', () => {
                                updateConnectionState();
                                console.warn('[Reverb] Connection unavailable');
                            });
                            pusher.connection.bind('disconnected', () => {
                                updateConnectionState();
                                console.warn('[Reverb] Disconnected');
                            });
                            pusher.connection.bind('connecting', () => {
                                updateConnectionState();
                            });
                            pusher.connection.bind('error', (err) => {
                                updateConnectionState();
                                console.warn('[Reverb] Connection error:', err);
                            });

                            // Periodic check to ensure badge status is never stale
                            if (!window._reverbStatusPollInterval) {
                                window._reverbStatusPollInterval = setInterval(() => {
                                    if (window._currentBookingNotifierInstance) {
                                        window._currentBookingNotifierInstance.reverbConnected = (pusher.connection.state === 'connected');
                                    }
                                }, 1000);
                            }
                        } else {
                            this.reverbConnected = true;
                        }

                        // Subscribe to public bookings channel (matching booking project)
                        if (!window._bookingChannelSubscribed) {
                            window._bookingChannelSubscribed = true;

                            const channel = window.Echo.channel('bookings');

                            const dispatchBookingEvent = (type, payload) => {
                                console.log(`[Reverb] ${type} received:`, payload);
                                if (window._currentBookingNotifierInstance) {
                                    if (type === 'NewBookingReceived') {
                                        window._currentBookingNotifierInstance.handleNewBooking(payload);
                                    } else if (type === 'BookingCancelled') {
                                        window._currentBookingNotifierInstance.handleBookingCancelled(payload);
                                    } else if (type === 'CheckInUpdated') {
                                        window._currentBookingNotifierInstance.handleCheckInUpdated(payload);
                                    }
                                }
                                // Broadcast DOM custom event for matrix and other components
                                window.dispatchEvent(new CustomEvent(type === 'NewBookingReceived' ? 'booking-received' : 'booking-updated', { detail: payload }));
                            };

                            channel.listen('.NewBookingReceived', (e) => dispatchBookingEvent('NewBookingReceived', e))
                                .listen('NewBookingReceived', (e) => dispatchBookingEvent('NewBookingReceived', e))
                                .listen('.BookingCancelled', (e) => dispatchBookingEvent('BookingCancelled', e))
                                .listen('BookingCancelled', (e) => dispatchBookingEvent('BookingCancelled', e))
                                .listen('.CheckInUpdated', (e) => dispatchBookingEvent('CheckInUpdated', e))
                                .listen('CheckInUpdated', (e) => dispatchBookingEvent('CheckInUpdated', e));
                        }
                    },

                    handleNewBooking(e) {
                        if (!e || (!e.id && !e.booking_code)) return;
                        const dedupKey = `booking-${e.id || ''}-${e.booking_code || ''}`;
                        if (!window._processedBookingAlerts) {
                            window._processedBookingAlerts = new Set();
                        }
                        if (window._processedBookingAlerts.has(dedupKey)) {
                            return;
                        }
                        window._processedBookingAlerts.add(dedupKey);
                        setTimeout(() => { window._processedBookingAlerts.delete(dedupKey); }, 15000);
                        const guest = e.guest_name || 'Guest';
                        const room = e.room_number ? `Room ${e.room_number}` : (e.room_id ? `Room ${e.room_id}` : 'Suite');
                        const code = e.booking_code || 'Booking';
                        const price = e.total_price ? Number(e.total_price) : 0;

                        const item = {
                            id: e.id,
                            booking_code: code,
                            guest_name: guest,
                            room_number: room,
                            total_price: price,
                            status: e.status || 'pending',
                            created_at: 'Just now',
                            timestamp: Date.now(),
                            type: 'booking',
                            title: '🎉 New Booking Received!',
                            message: `${guest} reserved ${room} — ${code}`,
                            read: false
                        };

                        this.notifications.unshift(item);
                        if (this.notifications.length > 50) {
                            this.notifications = this.notifications.slice(0, 50);
                        }
                        this.unreadCount++;
                        this.saveToStorage();

                        // Trigger bell bounce visual
                        this.triggerRing();

                        // Play reception bell chime
                        this.playChime();

                        // Show floating toast
                        this.addToast('🎉 New Booking Received!', `${guest} booked ${room} — ${code}`);

                        // Desktop notification if granted
                        this.sendDesktopNotification('🎉 New Booking Received!', `${guest} booked ${room} (${code})`, e.id);

                        // Dispatch event so matrix and other components can refresh
                        window.dispatchEvent(new CustomEvent('booking-received', { detail: e }));
                    },

                    handleBookingCancelled(e) {
                        const code = e.booking_code || 'Booking';
                        const item = {
                            id: e.id,
                            booking_code: code,
                            guest_name: 'Reservation Cancelled',
                            room_number: e.room_number || 'Room',
                            status: 'cancelled',
                            created_at: 'Just now',
                            timestamp: Date.now(),
                            type: 'cancelled',
                            title: '❌ Booking Cancelled',
                            message: `Booking ${code} was cancelled`,
                            read: false
                        };

                        this.notifications.unshift(item);
                        this.unreadCount++;
                        this.saveToStorage();
                        this.addToast('❌ Booking Cancelled', `Booking ${code} was cancelled.`);
                        window.dispatchEvent(new CustomEvent('booking-updated', { detail: e }));
                    },

                    handleCheckInUpdated(e) {
                        const code = e.booking_code || 'Booking';
                        const item = {
                            id: e.id,
                            booking_code: code,
                            guest_name: 'Guest Check-In',
                            room_number: e.room_number || 'Room',
                            status: e.status,
                            created_at: 'Just now',
                            timestamp: Date.now(),
                            type: 'checkin',
                            title: '✅ Check-In Updated',
                            message: `Booking ${code} status: ${e.status}`,
                            read: false
                        };

                        this.notifications.unshift(item);
                        this.unreadCount++;
                        this.saveToStorage();
                        this.addToast('✅ Check-In Updated', `Booking ${code} status: ${e.status}`);
                        window.dispatchEvent(new CustomEvent('booking-updated', { detail: e }));
                    },

                    triggerRing() {
                        this.isRinging = true;
                        setTimeout(() => { this.isRinging = false; }, 1200);
                    },

                    addToast(title, message) {
                        const toast = { title, message, visible: true, id: Date.now() + Math.random() };
                        this.toasts.push(toast);
                        setTimeout(() => {
                            toast.visible = false;
                            setTimeout(() => {
                                this.toasts = this.toasts.filter(t => t.id !== toast.id);
                            }, 300);
                        }, 6000);
                    },

                    updateUnreadCount() {
                        this.unreadCount = this.notifications.filter(n => !n.read).length;
                    },

                    markAllAsRead() {
                        this.notifications.forEach(n => { n.read = true; });
                        this.unreadCount = 0;
                        this.saveToStorage();
                    },

                    clearNotifications() {
                        this.notifications = [];
                        this.unreadCount = 0;
                        this.saveToStorage();
                    },

                    saveToStorage() {
                        try {
                            localStorage.setItem('agro_admin_notifications', JSON.stringify(this.notifications));
                        } catch(e) {}
                        this.updateUnreadCount();
                    },

                    toggleSound() {
                        this.soundEnabled = !this.soundEnabled;
                        localStorage.setItem('agro_admin_notif_sound', this.soundEnabled ? 'true' : 'false');
                        if (this.soundEnabled) {
                            this.playChime();
                        }
                    },

                    playChime() {
                        if (!this.soundEnabled) return;
                        try {
                            const AudioCtx = window.AudioContext || window.webkitAudioContext;
                            if (!AudioCtx) return;
                            const ctx = new AudioCtx();
                            if (ctx.state === 'suspended') {
                                ctx.resume();
                            }
                            const now = ctx.currentTime;

                            // Note 1: D5 (587.33Hz)
                            const osc1 = ctx.createOscillator();
                            const gain1 = ctx.createGain();
                            osc1.type = 'sine';
                            osc1.frequency.setValueAtTime(587.33, now);
                            gain1.gain.setValueAtTime(0.25, now);
                            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                            osc1.connect(gain1);
                            gain1.connect(ctx.destination);
                            osc1.start(now);
                            osc1.stop(now + 0.5);

                            // Note 2: A5 (880Hz)
                            const osc2 = ctx.createOscillator();
                            const gain2 = ctx.createGain();
                            osc2.type = 'sine';
                            osc2.frequency.setValueAtTime(880, now + 0.1);
                            gain2.gain.setValueAtTime(0.3, now + 0.1);
                            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                            osc2.connect(gain2);
                            gain2.connect(ctx.destination);
                            osc2.start(now + 0.1);
                            osc2.stop(now + 0.8);
                        } catch(e) {}
                    },

                    requestDesktopPermission() {
                        if ('Notification' in window) {
                            Notification.requestPermission().then((permission) => {
                                this.desktopPermissionNeeded = (permission === 'default');
                                if (permission === 'granted') {
                                    new Notification('Agro-Inland Resort', {
                                        body: 'Real-time booking notifications enabled!',
                                        icon: '/storage/assets/AIR-LOGO.png'
                                    });
                                }
                            });
                        }
                    },

                    sendDesktopNotification(title, message, bookingId) {
                        if ('Notification' in window && Notification.permission === 'granted') {
                            const notif = new Notification(title, {
                                body: message,
                                icon: '/storage/assets/AIR-LOGO.png',
                                tag: 'booking-' + (bookingId || Date.now())
                            });
                            notif.onclick = () => {
                                window.focus();
                                if (bookingId) {
                                    window.location.href = `/bookings/show/${bookingId}`;
                                }
                            };
                        }
                    }
                };
            }
        </script>
</div>
