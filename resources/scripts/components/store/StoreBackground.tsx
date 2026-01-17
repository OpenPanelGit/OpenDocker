import { useEffect, useRef } from 'react';
import { useStoreActions, useStoreState } from '@/state/hooks';
import http from '@/api/http';

const StoreBackground = () => {
    const updateUserData = useStoreActions((actions) => actions.user.updateUserData);
    const user = useStoreState((state) => state.user.data);
    const userRef = useRef(user);

    // Keep the ref updated with the latest user data (balance, rate, etc.)
    // but DON'T restart the interval effect below when user changes.
    useEffect(() => {
        userRef.current = user;
    }, [user]);

    useEffect(() => {
        // Initial safety check
        if (!userRef.current) return;

        console.log('[Store] Background worker started.');

        const sync = () => {
            http.post('/api/client/store/afk')
                .then(({ data }) => {
                    if (data.success) {
                        updateUserData({
                            coins: Number(data.balance),
                            rate: Number(data.rate)
                        });
                        if (data.gain) {
                            console.log(`[Store] Sync complete. Gained: ${data.gain.toFixed(4)}. Total: ${data.balance}`);
                        }
                    }
                })
                .catch((error) => console.error('[Store] Sync failed:', error));
        };

        // 1. Initial sync on load to set start time and catch up
        sync();

        // 2. Per-second smooth visual increment
        const tickInterval = setInterval(() => {
            const u = userRef.current;
            if (u && u.rate > 0) {
                // local visual bump
                const increment = u.rate / 60;
                updateUserData({ coins: u.coins + increment });
            }
        }, 1000);

        // 3. Regular server synchronization (every 30s for better persistence)
        const syncInterval = setInterval(sync, 10000);

        return () => {
            clearInterval(tickInterval);
            clearInterval(syncInterval);
        };
    }, []); // Empty dependency array means this ONLY runs once on mount

    return null;
};

export default StoreBackground;
